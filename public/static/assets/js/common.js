var cissCommonHeader = {
    oHeaderInput: $("#ciss-header-input"),
    oComplete: $(".ciss-auto-complete"),
    timer: null,
    autoCompleteIndex: -1,
    autoCompleteArr: [
        { key: "model_suggest", val: "型号规格", column: "specification_model_no" },
        { key: "man_suggest", val: "生产厂商", column: "manufacturer_name" },
        { key: "ming_suggest", val: "元器件名称", column: "name" }
    ],
    init: function() {
        this.inputChange();
        this.searchClick();
        this.inputOnBlur();
        this.getMsg();
        this.closeMsgModalClick();
        //  this.windowScrollTopIcon();
        //   this.nikeNameReset();
        this.toogleSlideBarClick();
    },
    // 收起侧边导航
    toogleSlideBarClick: function() {
        $(".toogle-slidebar").click(function() {
            var $slideBar = $(".slidebar")
            if ($slideBar.hasClass("show")) {
                $slideBar.hide(300).removeClass("show");
                $(".container-fluid").css("marginLeft", 0);
            } else {
                $slideBar.show(300).addClass("show");
                $(".container-fluid").css("marginLeft", "210px");
            }
        });
    },
    // 搜索按钮点击事件
    searchClick: function() {
        var that = this;
        $("#headerSearch").click(function() {
            var val = that.oHeaderInput.val();
            window.location.href = "/index/search/index?q=" + val;
        })
    },
    // 搜索框失去焦点
    inputOnBlur: function() {
        var that = this;
        this.oHeaderInput.on("blur", function() {
            setTimeout(function() {
                that.oComplete.html("");
            }, 500);

        });
    },
    // 监听搜索框输入 ，加载搜索提示
    inputChange: function() {
        var that = this;
        this.oHeaderInput.on("keyup", function(e) {
            //   clearTimeout(that.timer);
            var val = $(this).val();
            var index = that.autoCompleteIndex;
            var autoCompleteItem = $(".auto-complete-item");
            if (e.keyCode === 40) {
                index++;
                if (index >= autoCompleteItem.length) {
                    index = 0
                }
                that.autoCompleteIndex = index;
                autoCompleteItem.removeClass("active");
                autoCompleteItem.eq(index).addClass("active");
                var autoCompleteText = autoCompleteItem.eq(index).attr("data-text")
                that.oHeaderInput.val(autoCompleteText);
                return true;
            }
            if (e.keyCode === 38) {
                index--;
                if (index <= -1) {
                    index = autoCompleteItem.length - 1;
                }
                that.autoCompleteIndex = index;
                autoCompleteItem.removeClass("active");
                autoCompleteItem.eq(index).addClass("active");
                var autoCompleteText = autoCompleteItem.eq(index).attr("data-text");
                that.oHeaderInput.val(autoCompleteText);
                return true;
            }
            if (e.keyCode === 13) {
                if ($(".auto-complete-item.active").length > 0) {
                    window.location.href = "/index/search/index?column=" + $(".auto-complete-item.active").attr("data-column") + "&q=" + $(".auto-complete-item.active").attr("data-text");
                    return;
                }
                window.location.href = "/index/search/index?q=" + val;
                return;
            }
            // that.timer= setTimeout(function(){
            that.getAutoCompletData(val)
                // },100)
        })
    },

    // 获取搜索提示内容
    getAutoCompletData: function(val) {
        var that = this;
        $(".auto-complete-item").removeClass("active");
        $.get("/index/search/search_suggest?key=" + val, function(res) {
            var html = "";
            if (res.status === 1) {
                var sugData = res.data;
                var arr = [];
                for (var i = 0, lg = that.autoCompleteArr.length; i < lg; i++) {
                    var jLg = sugData[that.autoCompleteArr[i].key].length;
                    var disSug = jLg < 1 ? 'none' : 'block';
                    html +=
                        '<dl  style="display:' + disSug + '">' +
                        '<dt class="search-autoComplete-title"><span>' + that.autoCompleteArr[i].val + '</span></dt>';

                    for (var j = 0; j < jLg; j++) {
                        html +=
                            "<dd data-text='" + sugData[that.autoCompleteArr[i].key][j].text + "' data-column='" + that.autoCompleteArr[i].column + "' class='auto-complete-item'>" +
                            "<a  href='/index/search/index?column=" + that.autoCompleteArr[i].column + "&q=" + sugData[that.autoCompleteArr[i].key][j].text + "' >" + sugData[that.autoCompleteArr[i].key][j].text + " </a>" +
                            "</dd>"
                    }
                    html += '</dl>'
                }
                that.oComplete.html(html)
            }
        })
    },
    // 获取最新通知
    getMsg: function(close) {
        var that = this;
        $.get("/index/index/getLastpdatedMsgAction", function(res) {
            if (res.status != 1) {
                return false;
            }
            if (res.data != "") {
                $(".message-modal").attr("data-id", res.data.id);
                if (close) {
                    that.closeMsgModal();
                    return false;
                }
                var date = res.data.created_at;
                var name = res.data.name;
                $(".eda-modal-name").text(name);
                $(".eda-modal-date").text(date);
                $(".message-modal").show(300);
            }
        })
    },
    // 关闭通知点击事件
    closeMsgModalClick: function() {
        var that = this;
        $(".message-modal .close").click(function() {
            that.closeMsgModal();
        })
    },
    // 关闭通知框
    closeMsgModal: function() {
        var id = $(".message-modal").attr("data-id");
        $(".message-modal").hide(300);
        if (!id) {
            return false;
        }
        $.post("/index/index/readUpdatedMsgAction", {
            id: id
        }, function(res) {
            if (res.status != 1) {
                res.msg("错误..")
                return false;
            }

            $(".message-modal").hide(300);
        }).error(function(e) {
            layer.msg("关闭通知栏返回错误")
        })
    }
};
$(function() {
    cissCommonHeader.init();
});

var cissCommon = {
    /**
     * 设置cookie
     * @param c_name
     * @param value
     * @param expiredays
     */
    setCookie: function(c_name, value, expiredays) {
        var exdate = new Date();
        exdate.setDate(exdate.getDate() + expiredays);
        document.cookie = c_name + "=" + encodeURIComponent(value) +
            ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString()) + ";path=/";
    },
    /**
     * 获取cookie
     * @param c_name
     * @returns {string}
     */
    getCookie: function(c_name) {
        /*if (document.cookie.length > 0) {
            c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) c_end = document.cookie.length;
                return decodeURIComponent(document.cookie.substring(c_start, c_end))
            }
        }
        return "";*/
    },

    parseURL: function(url) {
        var a = document.createElement('a');
        a.href = url;
        return {
            source: url,
            protocol: a.protocol.replace(':', ''),
            host: a.hostname,
            port: a.port,
            query: a.search,
            file: (a.pathname.match(/\/([^\/?#]+)$/i) || [, ''])[1],
            hash: a.hash.replace('#', ''),
            path: a.pathname.replace(/^([^\/])/, '/$1'),
            relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [, ''])[1],
            segments: a.pathname.replace(/^\//, '').split('/'),
            params: (function() {
                var ret = {},
                    seg = a.search.replace(/^\?/, '').split('&'),
                    len = seg.length,
                    i = 0,
                    s;
                for (; i < len; i++) {
                    if (!seg[i]) {
                        continue;
                    }
                    s = seg[i].split('=');
                    ret[s[0]] = s[1];
                }
                return ret;
            })()
        }
    },

    /**
     * 解析URL
     * @param path
     * @returns {{path: *, query: string, hash: string}}
     */
    parsePath: function(path) {
        var path = path || window.location.pathname;
        var hash = '';
        var query = '';

        var hashIndex = path.indexOf('#');
        if (hashIndex >= 0) {
            hash = path.slice(hashIndex);
            path = path.slice(0, hashIndex);
        }

        var queryIndex = path.indexOf('?');
        if (queryIndex >= 0) {
            query = path.slice(queryIndex + 1);
            path = path.slice(0, queryIndex);
        }

        return {
            path: path,
            query: query,
            hash: hash
        }
    },
    /**
     * 获取当前目录
     * @returns {string}
     */
    getPath: function(url) {
        var url = url ? url : window.location.href;
        var pathInfo = this.parseURL(url);
        var currentPath = pathInfo.path ? pathInfo.path : '/';
        if (currentPath === '/') {
            currentPath = '/home';
        }
        return currentPath.toLowerCase();
    },

    /**
     * 获取GET参数
     * @param  {[type]}
     * @param  {[type]}
     * @return {[type]}
     */
    getUrlParam: function(key, url) {
        var url = url ? url : window.location.href;
        var c = new RegExp("(?:^|&|[?]|[/])" + key + "=([^&]*)");
        var d = c.exec(url);
        return d ? decodeURIComponent(d[1].replace(/\+/g, '%20')) : null
    },
    /**
     * 页面回退 取消按钮操作
     * 
     */
    goBack: function() {
        if ((window.navigator.userAgent.indexOf('MSIE') >= 0) && (window.navigator.userAgent.indexOf('Opera') < 0)) { // IE 
            if (history.length > 0) {
                window.history.go(-1);
            } else {
                window.opener = null;
                window.close();
            }
        } else { //非IE浏览器 
            if (window.navigator.userAgent.indexOf('Firefox') >= 0 ||
                window.navigator.userAgent.indexOf('Opera') >= 0 ||
                window.navigator.userAgent.indexOf('Safari') >= 0 ||
                window.navigator.userAgent.indexOf('Chrome') >= 0 ||
                window.navigator.userAgent.indexOf('WebKit') >= 0) {

                if (window.history.length > 1) {
                    window.history.go(-1);
                } else {
                    window.opener = null;
                    window.close();
                }
            } else { //未知的浏览器 
                window.history.go(-1);
            }
        }
    }
}