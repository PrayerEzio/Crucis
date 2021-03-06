@extends('Admin.main')

@section('body')
    <div class="wrapper wrapper-content animated fadeInRight blog">
        <div class="row">
            <div class="col-sm-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>{{ isset($data->id) ? '编辑' : '新增' }}权限 <small></small></h5>
                        <div class="ibox-tools">
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content">
                        <form method="post" action="" class="form-horizontal" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label class="col-sm-2 control-label">权限名称</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="name" value="{{ $data->name or '' }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">权限显示名</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="display_name" value="{{ $data->display_name or '' }}"> <span class="help-block m-b-none"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">描述</label>
                                <div class="col-sm-10">
                                    <input type="text" placeholder="描述" class="form-control" name="description" value="{{ $data->description or '' }}">
                                </div>
                            </div>
                            <div class="hr-line-dashed"></div>
                            <div class="form-group">
                                <div class="col-sm-4 col-sm-offset-2">
                                    <button class="btn btn-primary" type="submit">保存</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <!-- 实例化编辑器 -->
    <script type="text/javascript">
        var ue = UE.getEditor('container');
        ue.ready(function(){
            ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
        });
    </script>
@endsection