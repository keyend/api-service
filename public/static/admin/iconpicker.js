;layui.define(function(e) {
    e("iconpicker", {
        btn: null,
        input: null,
        replace: true,
        done: function() {},
        render(opts) {
            var t = this, $ = layui.$;
            t = $.extend(this, opts),
            t.btn = $(opts.elem),
            t.input = $(opts.elem)
            t.replace && (
            t.btn.addClass("layui-unselect layui-iconpicker"),
            t.btn.append('<span class="layui-btn layui-btn-normal"><i class="layui-icon layui-icon-snowflake"></i></span>')),
            t.btn.unbind().on("click", function() {
                t.picker(t.done)
            })
        },
        picker(fn) {
            parent.layer.open({
                type: 2,
                title: false,
                area: ['825px', '675px'],
                fixed: false,
                content: '/album/admin/album/icon.html',
                success: function(o, index){
                    var icon = parent.layer.getChildFrame(".layui-icon-wrap", index), listener, 
                    frame = o.find('iframe')[0].contentWindow;
                    icon.on("click", function() {
                        setTimeout(function() {
                            frame.getItem(function(values) {
                                if (values) {
                                    clearInterval(listener),
                                    typeof(fn)=='function'&&fn.call(e, values);
                                    parent.layer.close(index)
                                }
                            })
                        }, 100)
                    })
                }
            })
        }
    })
})