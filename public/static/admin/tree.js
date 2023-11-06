var table;
layui.use(['treetable', 'laytpl', 'form'], function() {
    var $ = layui.$, laytpl = layui.laytpl;
    table = {
        options: {
            where: {},
            params: {
                page: 1,
                limit: 9999
            },
            callback: {
                beforeCollapse(o) {
                    table.set('rule_' + o.item.id, null);
                    return true
                },
                beforeExpand(o) {
                    table.set('rule_' + o.item.id, '1');
                    return true
                }
            }
        },
        getTreeOption(res) {
            return $.extend(this.options, {
                elem: this.options.elem,
                nodes: this.parseData(res),
                layout: this.options.cols[0]
            })
        },
        parseData(data) {
            data.forEach((v,i) => {
                data[i].spread = this.getSpread(v.id),
                v.children&&(data[i].children=this.parseData(v.children))
            });
            return data;
        },
        getSpread(id) {
            return this.get('rule_' + id) == '1';
        },
        get(name) {
            if (window['localStorage']) {
                let obj = JSON.parse(localStorage.getItem(name)||'{"value": ""}');
                return obj.value;
            }
            return ''
        },
        set(name, value) {
            if (window['localStorage']) {
                if (value === null) {
                    localStorage.removeItem(name)
                } else {
                    let obj = {value: value};
                    localStorage.setItem(name, JSON.stringify(obj))
                }
            }
        },
        render(options) {
            this.options = $.extend(this.options, options),
            this.renderTable(this.options.elem, this.options)
        },
        reload(elem, params) {
            this.options.elem = '#' + elem,
            this.options = $.extend(this.options, params),
            this.renderTable(this.options.elem, this.options)
        },
        ps(url,obj){
            Object.keys(obj).map(v => (url=url+(url.indexOf('?')==-1?'?':'&')+v+'='+encodeURIComponent(obj[v])));
            return url;
        },
        renderTable(e, opts) {
            var url = this.options.url;
            url = this.ps(url,this.options.where),
            url = this.ps(url,this.options.params),
            console.log(layui.form);
            ns.silent(url, {}, res => {
                $(e).empty(),
                res.data.list = this.getTreeOption(res.data.list),
                layui.treetable(res.data.list)
            })
        },
        getAction(row) {
            let template = document.getElementById('actionTpl').innerHTML;
            return laytpl(template).render(row);
        }
    }
})