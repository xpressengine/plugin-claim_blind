<?php
namespace Xpressengine\Plugins\ClaimBlind;

use Route;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\ClaimBlind\Models\ClaimBlind;
use Illuminate\Database\Schema\Blueprint;
use Schema;
use Xpressengine\Plugins\Board\Models\Board;
use Xpressengine\Plugins\Comment\Models\Comment;

class Plugin extends AbstractPlugin
{
    public function register()
    {
        app()->singleton(Handler::class, function ($app) {
            $proxyClass = app('xe.interception')->proxy(Handler::class, 'XeClaimBlind');
            return new $proxyClass(app('xe.config'));
        });
        app()->alias(Handler::class, 'xe.xe_claim_blind.handler');
    }

    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        $this->route();
        $this->registerIntercept();

        app('xe.xe_claim_blind.handler');
//        $aa = ClaimBlind:get();
    }

    protected function registerIntercept()
    {
        intercept(
            'Xpressengine\Plugins\Claim\Handler@add',
            'custom.claim-add',
            function ($func, $targetId, $author, $shortCut) {
                $handler = app('xe.claim.handler');

                $item = $func($targetId, $author, $shortCut);
                $count = $handler->count($targetId);

                $configName = 'xe_claim_blind';
                $config = app('xe.config')->get($configName);
                $configBoard = $config->get('board_count');
                $configComment = $config->get('comment_count');

                if ($item->claim_type == 'module/board@board' && $count >= $configBoard) {
                    $blindItem = ClaimBlind::where('claim_type', $item->claim_type)->where('target_id', $item->target_id)->first();
                    if ($blindItem == null) {
                        $documentItem = \Xpressengine\Document\Models\Document::find($targetId);

                        $blindItem = new ClaimBlind;
                        $blindItem->claim_count = $count;
                        $blindItem->claim_type = $item->claim_type;
                        $blindItem->target_id = $item->target_id;
                        $blindItem->origin_title = $documentItem->title;
                        $blindItem->origin_content = $documentItem->content;
                        $blindItem->owner_user_id = $documentItem->user_id;
                        $blindItem->short_cut = $item->short_cut;
                        $blindItem->save();

                        // bind
                        $board = Board::division($documentItem->instance_id)->find($documentItem->id);
                        $board->title = '블라인드 처리된 게시물';
                        $board->content = '<p>블라인드 처리된 게시물 입니다.</p>';
                        $board->save();

                    }
                } elseif($item->claim_type == 'comment' && $count >= $configComment) {
                    $blindItem = ClaimBlind::where('claim_type', $item->claim_type)->where('target_id', $item->target_id)->first();
                    if ($blindItem == null) {
                        $documentItem = \Xpressengine\Document\Models\Document::find($targetId);

                        $blindItem = new ClaimBlind;
                        $blindItem->claim_count = $count;
                        $blindItem->claim_type = $item->claim_type;
                        $blindItem->target_id = $item->target_id;
                        $blindItem->origin_title = $documentItem->pure_content;
                        $blindItem->origin_content = $documentItem->content;
                        $blindItem->owner_user_id = $documentItem->user_id;
                        $blindItem->short_cut = $item->short_cut;
                        $blindItem->save();

                        // bind
                        $comment = Comment::division($documentItem->instance_id)->find($documentItem->id);
                        $comment->title = '';
                        $comment->content = '<p>블라인드 처리된 댓글 입니다.</p>';
                        $comment->save();
                    }
                }

                return $item;
            }
        );
    }

    protected function route()
    {
//        app('xe.register')->push(
//            'settings/menu',
//            'contents.claim.blind',
//            [
//                'title' => 'Claim Blinds',
//                'description' => 'Blinded list',
//                'display' => true,
//                'ordering' => 5000
//            ]
//        );
        app('xe.register')->push(
            'settings/menu',
            'contents.claim_blind',
            [
                'title' => 'Claim Blinds',
                'description' => 'Blinded list',
                'display' => true,
                'ordering' => 5001
            ]
        );

        Route::settings(
            $this->getId(),
            function () {
                Route::group(
                    ['namespace' => 'Xpressengine\Plugins\ClaimBlind\Controllers'],
                    function () {
                        Route::get(
                            '/',
                            [
                                'as' => 'xe_claim_blind::setting.index',
                                'uses' => 'SettingController@index',
//                                'settings_menu' => 'contents.claim.blind',
                                'settings_menu' => 'contents.claim_blind',
                            ]
                        );
                        Route::get('/restore', ['as' => 'xe_claim_blind::setting.restore', 'uses' => 'SettingController@restore']);
                    }
                );
            }
        );
    }

    public function getSettingsURI()
    {
        return route('xe_claim_blind::setting.index');
    }

    /**
     * 플러그인이 활성화될 때 실행할 코드를 여기에 작성한다.
     *
     * @param string|null $installedVersion 현재 XpressEngine에 설치된 플러그인의 버전정보
     *
     * @return void
     */
    public function activate($installedVersion = null)
    {
        // implement code
    }

    /**
     * 플러그인을 설치한다. 플러그인이 설치될 때 실행할 코드를 여기에 작성한다
     *
     * @return void
     */
    public function install()
    {
        if (!Schema::hasTable('claim_blinds')) {
            Schema::create(
                'claim_blinds',
                function (Blueprint $table) {
                    $table->engine = "InnoDB";

                    $table->increments('id');
                    $table->bigInteger('claim_count')->default(0);
                    $table->string('claim_type', 100)->index();
                    $table->string('target_id', 100)->index();
                    $table->string('origin_title', 255);
                    $table->text('origin_content');
                    $table->string('target_type')->nullable();
                    $table->string('owner_user_id', 100)->index();
                    $table->string('short_cut', 255);
                    $table->integer('status')->default(0);
                    $table->timestamp('created_at');
                    $table->timestamp('updated_at');
                }
            );
        }

        $configName = 'xe_claim_blind';
        $config = app('xe.config')->get($configName);
        if ($config == null) {
            app('xe.config')->set($configName, [
                'board_count' => 5,
                'comment_count' => 5,
            ]);
        }
    }

    /**
     * 해당 플러그인이 설치된 상태라면 true, 설치되어있지 않다면 false를 반환한다.
     * 이 메소드를 구현하지 않았다면 기본적으로 설치된 상태(true)를 반환한다.
     *
     * @return boolean 플러그인의 설치 유무
     */
    public function checkInstalled()
    {
        // implement code

        return parent::checkInstalled();
    }

    /**
     * 플러그인을 업데이트한다.
     *
     * @return void
     */
    public function update()
    {
        // implement code
    }

    /**
     * 해당 플러그인이 최신 상태로 업데이트가 된 상태라면 true, 업데이트가 필요한 상태라면 false를 반환함.
     * 이 메소드를 구현하지 않았다면 기본적으로 최신업데이트 상태임(true)을 반환함.
     *
     * @return boolean 플러그인의 설치 유무,
     */
    public function checkUpdated()
    {
        // implement code

        return parent::checkUpdated();
    }
}
