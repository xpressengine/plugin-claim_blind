<?php
namespace Xpressengine\Plugins\ClaimBlind\Controllers;

use App\Http\Controllers\Controller;
use XePresenter;
use Xpressengine\Http\Request;
use Carbon\Carbon;
use Xpressengine\Plugins\ClaimBlind\Models\ClaimBlind;
use Xpressengine\Plugins\Board\Models\Board;
use Xpressengine\Plugins\Comment\Models\Comment;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $query = ClaimBlind::query();

        $current = Carbon::now();
        //기간 검색
        if ($endDate = $request->get('end_date', $current->format('Y-m-d'))) {
            $query = $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        if ($startDate = $request->get('start_date', $current->subDay(7)->format('Y-m-d'))) {
            $query = $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($userEmail = $request->get('user_email')) {
            $writers = \XeUser::where(
                'email',
                'like',
                '%' . $userEmail . '%'
            )->selectRaw('id')->get();

            $writerIds = [];
            foreach ($writers as $writer) {
                $writerIds[] = $writer['id'];
            }
            $query = $query->whereIn('owner_user_id', $writerIds);
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(40)->appends($request->except('page'));

        return XePresenter::make('xe_claim_blind::views.setting.index', [
            'items' => $items,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userEmail' => $userEmail,
        ]);
    }

    public function restore(Request $request)
    {
        $id = $request->get('id');
        $item = ClaimBlind::find($id);
        if ($item == null) {
            $exception = new \Xpressengine\Support\Exceptions\HttpXpressengineException(
                [], 500
            );
            $exception->setMessage('아이템을 찾을 수 없습니다.');
            throw $exception;
        }

        if ($item->claim_type == 'module/board@board') {
            $documentItem = \Xpressengine\Document\Models\Document::find($item->target_id);
            $board = Board::division($documentItem->instance_id)->find($documentItem->id);
            $board->title = $item->origin_title;
            $board->content = $item->origin_content;
            $board->save();

        } elseif ($item->claim_type == 'comment') {
            $documentItem = \Xpressengine\Document\Models\Document::find($item->target_id);
            $comment = Comment::division($documentItem->instance_id)->find($documentItem->id);
            $comment->title = '';
            $comment->content = $item->origin_content;
            $comment->save();
        }

        $item->status = 10;
        $item->save();

        return redirect()->route('xe_claim_blind::setting.index');
    }
}
