@section('page_title')
    <h2>신고 블라인드 관리</h2>
@stop

<div class="container-fluid container-fluid--part claim-blind">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel-group">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h3 class="panel-title">
                            </h3>
                        </div>
                        <div class="pull-right">
                            <div class="input-group search-group">
                                <form>
                                    <div class="search-btn-group">
                                        <div class="input-group-btn">
                                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="xi-calendar-check"></i></button>
                                        </div>
                                        <div class="search-input-group">
                                            <input type="text" name="start_date" class="form-control" placeholder="{{xe_trans('xe::enterStartDate')}}" value="{{ $startDate }}" >
                                            <input type="text" name="end_date" class="form-control" placeholder="{{xe_trans('xe::enterEndDate')}}" value="{{ $endDate }}" >
                                        </div>
                                    </div>

                                    <div>
                                        <div class="search-input-group">
                                            <input type="text" name="user_email" class="form-control" aria-label="Text input with dropdown button" placeholder="{{xe_trans('xe::enterEmail')}}" value="{{Request::get('user_email')}}">
                                            <button class="btn-link">
                                                <i class="xi-search"></i><span class="sr-only">{{xe_trans('xe::search')}}</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col">글번호</th>
                                <th scope="col">신고수</th>
                                <th scope="col">작성자</th>
                                <th scope="col">제목</th>
                                <th scope="col">블라인드일시</th>
                                <th scope="col">비고</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr class="info-row">
                                    <td>
                                        <a href="{{ $item->short_cut}}" target="_blank">{{ $item->target_id}}</a>
                                        <br>
                                        @if ($item->claim_type == 'module/board@board')
                                            게시판
                                        @elseif($item->claim_type == 'comment')
                                            댓글
                                        @endif
                                    </td>
                                    <td>{{ $item->claim_count}}</td>
                                    <td>{{ $item->user->getDisplayName() }}</td>
                                    <td>
                                        <a href="{{ $item->short_cut}}?claim_blind=force" target="_blank">{{ $item->origin_title}}</a> <br/>
                                        {{--<button class="btn_show_content">내용보기</button>--}}
                                    </td>
                                    <td>{{ $item->created_at->format('Y.m.d H:i:s') }}</td>
                                    <td>
                                        @if ($item->status == 0)
                                            <a href="{{route('xe_claim_blind::setting.restore', ['id'=>$item->id])}}" class="xe-btn">복구</a>
                                        @else
                                            복구된 게시물
                                        @endif
                                    </td>
                                </tr>
                                <tr class="origin-content origin-content-row" style="display:none;">
                                    <td colspan="6" style="padding-left: 40px; background-color:#ccc;">
                                        <div>{{$item->origin_content}}</div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($pagination = $items->render())
                        <div class="panel-footer">
                            <div class="pull-left">
                                <nav>
                                    {!! $pagination !!}
                                </nav>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        $('.claim-blind .btn_show_content').bind('click', function () {
            var $target = $(this).closest('tr').next('.origin-content-row');
            if ($target.is(':visible')) {
                $('.origin-content').hide();
            } else {
                $('.origin-content').hide();
                $target.slideDown(3000);
            }
        });
    });
</script>
