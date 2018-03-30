var loginPage = {
    $Account: $("#account"),
    $Pwd: $("#pwd"),
    sLoginUrl: '/index/login/loginAction',
    init: function() {
        this.submit();
        this.enterClick();
    },
    // 回车事件
    enterClick: function() {
        this.$Pwd.keyup(function(e) {
            if (e.keyCode == 13) {
                $(".submit-btn").trigger("click");
            }
        })
    },
    // 提交按钮点击事件
    submit: function() {
        var that = this;
        $(".submit-btn").click(function() {
            var account = that.$Account.val();
            var pwd = that.$Pwd.val();
            if (account === "") {
                layer.msg("请输入账号");
                return false
            }
            if (pwd.length < 8) {
                layer.msg("请输入长度大于等于8位的登录密码");
                return false;
            }
            layer.load();
            $.post(that.sLoginUrl, { username: account, password: pwd }, function(res, code) {
                layer.closeAll("loading");
                if (res.status == 0) {
                    layer.msg(res.data);
                    return false;
                }
                layer.msg("登录成功");
                window.location.href = "/index";
            });
        })
    }
};

loginPage.init();