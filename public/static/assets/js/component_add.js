var componentAddPage = {
    reqParams: {},
    init: function () {
        // 初始化联动菜单
        this.getCategory($("#category").val());
        this.categoryClick();

        //搜索更新自动提示栏
        this.autoComplete();
        // 
        this.autocopmpletClick();
        // 图片上传
        // this.uploadClick("upload-img");
        // datasheet上传
        // this.uploadClick("upload-datasheet");
        // 执行标准上传
        // this.uploadClick("upload-executive");
        // 外形尺寸图上传
        // this.uploadClick("upload-dimension");
        // 输入框change
        this.inputChange();
        // 提交 
        this.submitClick();
        // 获取所有厂商
        this.getManufactruerList();
    },
    // 获取小类菜单
    getCategory: function (category_id) {
        $.get("/index/component/getSubCategoriesAction", {
            category_id: category_id
        }, function (res) {
            if (res.status != 1) return;

            var html = "";
            $("#sub_category").html("");
            for (var i = 0; i < res.data.length; i++) {
                html += "<option value='" + res.data[i].id + "'>" + res.data[i].name + "</option>"
            }
            $("#sub_category").html(html);
        })
    },
    // 大类菜单点击事件
    categoryClick: function () {
        var that = this;
        $("#category").click(function () {
            var value = this.value;
            that.getCategory(value);
        })
    },
    //搜索更新自动提示栏
    autoComplete: function () {
        var that = this;
        $(".manufacturer-modal-input").keyup(function () {
            var val = this.value;
            that.autoCompleteData(val);
        })
    },
    // 获取所有厂商
    getManufactruerList: function () {
        var that = this;
        $.get("/index/component/getManufacturersAction", function (res) {
            if (res.status != 1) {
                return;
            }
            that.manufactruerList = res.data;
            that.autoCompleteData();
        })
    },
    // 更新自动提示词汇
    autoCompleteData: function (key) {
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
            pHtml += '<p class="autocomplet-item" data-id="' + newManufactruers[i].id + '" data-name="' + newManufactruers[i].name + '">' + newManufactruers[i].name + '</p>'
        }
        $(".autocomplet-list").html(pHtml);
    },
    // 搜索厂商提示点击事件
    autocopmpletClick: function () {
        var that = this;
        $(".autocomplet-list").delegate("p", "click", function () {
            var manufactruerId = $(this).attr("data-id");
            var manufactrueName = $(this).attr("data-name");
            $('#myModal').modal("hide");
            $(".manufacturer-modal-input").val(manufactrueName);
            $(".manufacturerName").text(manufactrueName);
            $("input[name=manufacturer_name]").val(manufactrueName);
            $("input[name=manufacturer_id]").val(manufactruerId);
            $(".manufacturerName").parents("tr").addClass("active");
            that.reqParams["manufacturer_id"] = manufactruerId;
        })
    },
    // 监听输入框修改事件
    inputChange: function () {
        var that = this;
        $("tbody input").change(function () {
            //data.datasheet_path = "" ;     // datasheet pdf 地址
            //data.executive_standard = "" ; // 执行标准 pdf 地址
            //data.components_picture = "" ; //  图片地址
            //data.dimension_picture = "" ;  //  外形尺寸图地址
            $(this).parents("tr").addClass("active");
            var name = $(this).attr("name");
            var val = this.value;
            that.reqParams[name] = val;
        })
    },
    // 上传文件点击事件
    uploadClick: function (id) {
        var that = this;
        var Uploader = Q.Uploader;
        var UPLOAD_URL = "/index/component/uploadFileAction"; // 上传地址
        var target = document.getElementById(id);
        var $target = $(target);
        var $textView = $target.siblings("p"); // 上传提示
        var type = $target.attr("data-type"); // 上传文件发送type
        var isImg = $target.attr("data-img") ? true : false; // 是否只能上传图片
        var sMsg = $target.attr("data-msg"); // 提示内容
        var $input = $("input[name=" + $target.attr("data-name") + "]");
        var uploader = new Uploader({
            url: UPLOAD_URL,
            target: target,
            multiple: false,
            //   allows: ".txt,.jpg,.png,.gif,.mp4,.zip", //允许上传的文件格式
            maxSize: 20 * 1024 * 1024, //允许上传的最大文件大小,字节,为0表示不限(仅对支持的浏览器生效)
            data: {type: type, component_pid: "123"},
            upName: "file",
            dataType: "text/html",
            auto: true,
            html5: false,
            on: {
                //添加之前触发
                add: function (task) {
                    layer.msg("上传中")

                    $textView.text(task.name + "文件上传中...")
                },
                //上传完成后触发
                complete: function (task) {
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
                                var html = "<a target="
                                _block
                                " href=" / " + path + "
                                ">预览</a>"
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
    submitClick: function () {
        var that = this;
        $(".submit-change").click(function (e) {
            var event = e || window.event;
            if (e.preventDefault) {
                event.preventDefault();
            } else {
                event.returnValue = false;
            }
            var params = {}
            var formData = $(".add-component-form").serializeArray();
            $.each(formData, function () {
                params[this.name] = this.value;
            });
            if (params.serial_no.trim() === "" || params.serial_no.length != 10) {
                layer.msg("请输入正确格式的物资编码");
                return false;
            }
            if (params.specification_model_no.trim() === "") {
                layer.msg("请输入型号规格");
                return false;
            }
            if (params.manufacturer_id.trim() === "") {
                layer.msg("请输入生产厂家");
                return false;
            }
            if (params.name.trim() === "") {
                layer.msg("请输入名称");
                return false;
            }
            layer.load();
            $.ajax({
                url: '/index/component/addAction',
                type: 'POST',
                dataType: 'json',
                // 当前修改用户id及 用户组id数组
                data: params,
                complete: function (xhr, textStatus) {
                },
                success: function (res, textStatus, xhr) {
                    layer.closeAll('loading');
                    if (res.status != 1) {
                        layer.msg(res.data)
                        return false;
                    }
                    layer.msg("新增元器件成功，跳转到完善信息页");
                    window.location.href = "/index/component/edit?sn=" + params.serial_no.trim();
                },
                error: function (xhr, textStatus, errorThrown) {
                    layer.closeAll('loading');
                    layer.msg("服务端错误")
                }
            });
        })
    }
};
$(function () {
    componentAddPage.init();
})