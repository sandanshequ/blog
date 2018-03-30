    var componentEditPage = {
        reqParams: {}, // 发送的参数
        manufactruerList: [], // 所有厂商
        init: function() {
            //搜索更新自动提示栏
            this.autoComplete();
            //
            this.autocopmpletClick();
            // 图片上传
            //  this.uploadClick("upload-img");
            // datasheet上传
            //  this.uploadClick("upload-datasheet");
            // 执行标准上传
            //  this.uploadClick("upload-executive");
            // 外形尺寸图上传
            //  this.uploadClick("upload-dimension");
            // 输入框change
            this.inputChange();
            // 提交
            this.submitClick();

            // 获取所有厂商
            this.getManufactruerList();

            // 储存原始数据
            this.initDefaultValue();

            this.showManufacturerModalClick();
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
        // 修改生产厂商按钮点击事件
        showManufacturerModalClick: function() {
            $(".showManufacturerModal").click(function() {
                $("#myModal").modal("show");
            })
        },
        //搜索更新自动提示栏
        autoComplete: function() {
            var that = this;
            $(".manufacturer-modal-input").keyup(function() {
                var val = this.value;
                that.autoCompleteData(val);
            })
        },
        // 获取所有厂商
        getManufactruerList: function() {
            var that = this;
            $.get("/index/component/getManufacturersAction", function(res) {
                if (res.status != 1) { return; }
                that.manufactruerList = res.data;
                that.autoCompleteData();
            })
        },
        // 更新自动提示词汇
        autoCompleteData: function(key) {
            var newManufactruers = [];
            var manufacturers = this.manufactruerList;
            $(".autocomplete-select").html("");
            $(".modal .autocomplet-list").html("");
            if (key) {
                for (var i = 0, l = manufacturers.length; i < l; i++) {
                    if (manufacturers[i].name.match(key)) {
                        newManufactruers.push(manufacturers[i]);
                    }
                }
            } else {
                newManufactruers = manufacturers;
            }
            var pHtml = "";
            for (var i = 0, l = newManufactruers.length; i < l; i++) {
                pHtml += "<p class='autocomplet-item' data-id='" + newManufactruers[i].id + "' data-name='" + newManufactruers[i].name + "' >" + newManufactruers[i].name + "</p>"
            }
            $(".autocomplet-list").html(pHtml);
        },
        // 搜索提示点击事件
        autocopmpletClick: function() {
            var that = this;
            $(".autocomplet-list").delegate("p", "click", function() {
                var manufactruerId = $(this).attr("data-id");
                var manufactrueName = $(this).attr("data-name");
                $('#myModal').modal("hide");
                $(".manufacturerName").text(manufactrueName);
                $("input[name=manufacturer_name]").val(manufactrueName);
                $("input[name=manufacturer_id]").val(manufactruerId);
                if ($("input[name=manufacturer_id]").attr("data-default") == manufactruerId) {
                    $(".manufacturerName").parents("tr").removeClass("active");
                } else {
                    $(".manufacturerName").parents("tr").addClass("active");
                }
                that.reqParams["manufacturer_id"] = manufactruerId;
            })
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
            var that = this;
            var Uploader = Q.Uploader;
            //var UPLOAD_URL = "/index/component/uploadFileAction"; // 上传地址
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
                //   allows: ".txt,.jpg,.png,.gif,.mp4,.zip", //允许上传的文件格式
                maxSize: 20 * 1024 * 1024, //允许上传的最大文件大小,字节,为0表示不限(仅对支持的浏览器生效)
                data: { type: type, component_pid: pid },
                upName: "file",
                dataType: "text/html",
                auto: true,
                html5: false,
                on: {
                    //添加之前触发
                    add: function(task) {
                        $textView.text(task.name + "文件上传中...")
                    },
                    //上传完成后触发
                    complete: function(task) {
                        //  console.log(task)
                        if (task.state != Uploader.COMPLETE) {
                            $textView.text(task.name + "文件上传失败")
                                //    console.log(task.name + ": " + Uploader.getStatusText(task.state) + "!");
                            return;
                        }
                        var res = task.response;
                        try {
                            res = $.parseJSON(res.replace(/<.*?>/ig, ""));
                            if (res.status != 1) {
                                layer.msg(sMsg + "--" + task.name + ",文件上传失败," + res.data);
                                $textView.text(task.name + "文件上传失败");
                                return false;
                            }

                            layer.msg(sMsg + "--" + task.name + ",文件上传成功");
                            $textView.text(task.name + "文件上传成功")

                            var path = res.data.file_path;
                            $input.val(path);

                            // 判断图片展示 和 上传文件预览问题 图片之前不存在和之前文件不存在
                            if (isImg) {
                                $target.parents("tr").find("img").attr("src", "/" + path);
                            } else {
                                if ($target.parents("tr").find(".view-box a").length >= 1) {
                                    $target.parents("tr").find(".view-box a").attr("href", "/" + path);
                                } else {
                                    var html = "<a target='_block' href='/" + path + "'>预览</a>"
                                    $target.parents("tr").find(".view-box").html(html);
                                }
                            }
                            // 高亮 修改信息保存
                            $target.parents("tr").addClass("active");
                            that.reqParams[$target.attr("data-name")] = path;
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

                if ($(".component-detail-tabs .active").length < 1) {
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
                            //window.history.go(-1);
                            if (document.referrer.match('/component/add')) {
                                window.location.href = '/index/component/list';
                                return;
                            }
                            window.location.href = document.referrer;

                        }, 300)
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        //  console.log("error");
                        layer.closeAll('loading');
                        layer.msg("服务端错误")
                    }
                });
            })
        }
    };

    $(function() {
        componentEditPage.init();
    })