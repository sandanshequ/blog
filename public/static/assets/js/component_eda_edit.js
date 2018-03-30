var componentEdaEdit = {
    reqParams: {},
    init: function() {
        this.inputChange();
        // 图片上传
        //  this.uploadClick("upload-ad-lib");
        // datasheet上传
        // this.uploadClick("upload-ad-footprint");
        // 提交修改
        this.submitClick();
        // 储存原始数据
        this.initDefaultValue();
    },
    // init default value 
    initDefaultValue: function() {
        var $input = $("tbody input");
        for (var i = 0, l = $input.length; i < l; i++) {
            var thisInput = $($input[i]);
            // radio 标签单独处理
            if (thisInput.attr("type") == "radio") {
                var radioChecked = $("input[name='" + thisInput.attr("name") + "']:checked").val();
                $($input[i]).attr("data-default", radioChecked);
            } else {
                $($input[i]).attr("data-default", $input[i].value);
            }

        }
    },
    // 监听输入框修改事件
    inputChange: function() {
        var that = this;
        $("tbody input").change(function() {
            //data.datasheet_path = "" ;     // datasheet pdf 地址
            //data.executive_standard = "" ; // 执行标准 pdf 地址
            //data.components_picture = "" ; //  图片地址
            //data.dimension_picture = "" ;  //  外形尺寸图地址
            var name = $(this).attr("name");
            var defaultVal = $(this).attr("data-default");
            var val = this.value;
            if (val != defaultVal) {
                $(this).parents("tr").addClass("active");
            } else {
                $(this).parents("tr").removeClass("active");
            }
            that.reqParams[name] = val;
        })
    },
    // 上传文件点击事件
    uploadClick: function(id) {
        // console.log("upload init")
        var Uploader = Q.Uploader;
        var target = document.getElementById(id);
        var $target = $(target);
        var $textView = $target.siblings("p"); // 上传提示
        var type = $target.attr("data-type"); // 上传文件发送type
        var isImg = $target.attr("data-img") ? true : false; // 是否只能上传图片
        var sMsg = $target.attr("data-msg"); // 提示内容
        var $input = $("input[name=" + $target.attr("data-name") + "]");
        var pid = $("input[name=pid]").val();
        var uploader = new Uploader({
            url: UPLOAD_URL,
            target: target,
            multiple: false,
            //allows: ".txt,.jpg,.png,.gif,.mp4,.zip", //允许上传的文件格式
            maxSize: 20 * 1024 * 1024, //允许上传的最大文件大小,字节,为0表示不限(仅对支持的浏览器生效)
            //每次上传都会发送的参数(POST方式)
            data: { type: type, component_pid: pid },
            upName: "file",
            html5: false,
            auto: true,
            dataType: "text/html", //服务器返回值类型
            on: {
                //添加之前触发
                add: function(task) {
                    $textView.text(task.name + "文件上传中...")
                        //自定义判断，返回false时该文件不会添加到上传队列
                        //return false;
                },
                //上传完成后触发
                complete: function(task) {
                    if (task.state != Uploader.COMPLETE) {
                        $textView.text(task.name + "文件上传失败")
                            //   console.log(task.name + ": " + Uploader.getStatusText(task.state) + "!");
                        return;
                    }
                    var res = task.response;
                    try {
                        res = $.parseJSON(res.replace(/<.*?>/ig, ""));
                        //  console.log(res);
                        if (res.status != 1) {
                            layer.msg(sMsg + "--" + task.name + ",文件上传失败," + res.data);
                            $textView.text(task.name + "文件上传失败");
                            return false;
                        }

                        layer.msg(sMsg + "--" + task.name + ",文件上传成功");
                        $textView.text(task.name + "文件上传成功")

                        var path = res.data.orig_name;
                        $input.val(path);
                        $target.parents("tr").addClass("active");
                    } catch (error) {
                        layer.msg(sMsg + "--" + task.name + ",文件上传失败");
                        $textView.text(task.name + "文件上传失败");
                    }

                }
            }
        });
    },
    // 数据修改提交
    submitClick: function() {
        var that = this;
        $(".submit-change").click(function(e) {
            var event = e || window.event;
            if (e.preventDefault) {
                event.preventDefault();
            } else {
                event.returnValue = false;
            }
            var params = {}
            var formData = $(".submit-component-data").serializeArray();
            $.each(formData, function() {
                params[this.name] = this.value;
            });

            if ($(".component-detail-tabs tr.active").length < 1) {
                layer.msg("您还没有修改数据")
                return false;
            }
            layer.load();
            $.ajax({
                url: SUBMIT_EDIT_DATA_URL,
                type: 'POST',
                dataType: 'json',
                // 当前修改用户id及 用户组id数组
                data: params,
                complete: function(xhr, textStatus) {
                    //called when complete
                },
                success: function(res, textStatus, xhr) {
                    layer.closeAll('loading');
                    if (res.status != 1) {
                        layer.msg(res.data)
                        return false;
                    }
                    layer.msg("提交修改成功");
                    setTimeout(function() {
                        window.location.href = document.referrer;
                    }, 300)
                },
                error: function(xhr, textStatus, errorThrown) {
                    // console.log("error");
                    layer.closeAll('loading');
                    layer.msg("服务端错误")
                }
            });
        })
    }
};

$(function() {
    componentEdaEdit.init();
})