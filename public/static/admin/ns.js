var ns = {
  // 当前窗口ID
  id: '',
  // 堆栈
  stock: [],
  // 缓存
  values: null,
  // 来源窗口
  from: null,
  // 当前页码
  page: 1,
  // 是否为子窗口
  ischild: 0,
  // 是否可重载
  isReload: false,
  /**
   * 静默方式POST
   * @param {*} url 
   * @param {*} data 
   * @param {*} fn 
   */
  silent(url, data, fn, opts) {
    let loading = top.layer.load(2);
    opts = opts || {},
    layui.$.ajax({
      type: 'POST',
      url: url,
      data: data,
      dataType: 'json',
      success: res => {
        top.layer.close(loading);
        if (res.code != 0) {
          top.layer.msg(res.message || res.msg, {time: 2000});
        } else {
          typeof(opts['msg'])=='undefined' && (opts.msg = !0),
          opts.msg != false ? parent.layer.msg("SUCCESS", {icon: 1, time: 800} , () => typeof(fn)=='function'&&fn.call(this, res)) : typeof(fn)=='function'&&fn.call(this, res)
        }
      }
    })
  },
  /**
   * 是否重载状态
   * @return boolean
   */
  getReload() {
    const ret = this.isReload;
    this.isReload = false;
    return ret
  },
  /**
   * 发送消息
   * @param {*} ds 
   */
  postMessage(ds) {
    ds.params.id && self.parent.frames[ds.params.id].contentWindow.postMessage(ds)
  },
  /**
   * 监听消息
   * @param {*} ds 
   */
  listener() {
    var that = this;
    window.addEventListener("message", receiveMessage, false);

    function receiveMessage(ev) {
      let res = ev.data, isReceived = false;
      console.log(that.id + "接收消息 => " + JSON.stringify(res));

      if (typeof(res) == 'object') {
        if (res.method == "wost") {
          isReceived = true,
          that.isReload = res.params.id == that.id && res.params.response.code == 0,
          window.removeEventListener("message", receiveMessage, false),
          console.log("监听窗口" + that.id + "子窗口 " + res.params.child_id + "__" + res.params.id + " ==> 提交完成");
        }
      }
    }
  },
  /**
   * IFRAME弹窗
   * @returns void
   */
  open(url, title, size, type, offset) {
    return new Promise(resolve => {
      let obj = window.parent;
      this.listener(),
      obj.ns.stock.push({
        id: this.id,
        title: title
      }),
      console.log(offset);
      obj.layer.open({
        type: type||2
        ,title: title || false
        ,shadeClose: title ? false : true
        ,content: url
        ,area: size||['60%', '60%']
        ,offset: offset || 'auto'
        ,anim: offset == 'r' ? 'slideLeft' : 0
        ,closeBtn: offset ? 0 : 1
        ,end: () => {
          const value = self.parent.ns.values;
          self.parent.ns.values = null,
          value && resolve(value)
        }
      })
    })
  },
  /**
   * 关闭当前弹窗
   */
  close(value) {
    var index = self.parent.layer.getFrameIndex(this.id);
    self.parent.layer.close(index);
  },
  /**
   * 获取当前表格选中字段
   * @param obj 表格回调参数
   * @param field 要获取的字段
   */
  tableSelected(obj, field) {
    if (typeof(layui.table) !== 'undefined') {
			let data = layui.table.checkStatus(obj.config.id).data;
			if (data.length === 0) {
				return "";
			}
      if (field) {
        let ids = [];
        for (let i = 0; i < data.length; i++) {
          ids.push(data[i][field])
        }
        data = ids;
      }
			return data;
    }
    return [];
  },
  /**
   * IFRAME弹窗POST
   * @param {*} url 
   * @param {*} data 
   * @param {*} fn 
   */
  wost(url, data, fn) {
    $.post(url, data, res => {
      self.parent.ns.values = {
        method: 'wost',
        params: {
          id: this.from ? this.from.id : '',
          child_id: this.id,
          url: url,
          data: data,
          response: res
        }
      },
      this.ischild && this.postMessage(self.parent.ns.values);
      if(0 == res.code) {
        res.message == 'success' ? parent.layer.msg("SUCCESS", {icon: 1, time: 800}, () => typeof(fn)=='function'&&fn.call(this, res)) : parent.layer.alert(res.message || res.msg, (index) => {
          parent.layer.close(index),
          parent.layer.msg("SUCCESS", {icon: 1, time: 800}, () => typeof(fn)=='function'&&fn.call(this, res))
        })
      } else {
        parent.layer.alert(res.message || res.msg, { 'icon': 2 });
      }
    }, 'json');
  },  
  /**
   * 图片地址转换
   * @param {*} url 
   */
  img(url) {
    url = url || '';
    if(url.indexOf('://')==-1){
      return location.origin+url
    }else{
      return url
    }
  },
  /**
   * 相册
   * @param {*} fn 
   */
  album(fn, limit) {
    limit=limit||9999;
    parent.layer.open({
			type: 2,
			title: '图片管理',
			area: ['825px', '675px'],
			fixed: false, //不固定
			btn: ['保存', '返回'],
			content: '/album/admin/album.html?limit=' + limit,
			yes: function (index, e) {
        e = e.find('iframe')[0].contentWindow,
				e.getCheckItem(obj => typeof(fn)=='function'&&fn.call(e, obj)),
        parent.layer.close(index)
			}
		})
  },
  /**
   * 会员选择组件
   * @param {*} v 
   */
  memberSelectRender(v) {
    let that = $(v), mapper = that.data('mapper')
    , member_id = that.attr('id');

    try {
      typeof(mapper) == 'string' && (mapper = JSON.parse(mapper));
    } catch(e) {
      throw new Error("[错误] 会员选择组件加入失败", e);
    }

    function showPopup(fn) {
      parent.layer.open({
        type: 2,
        title: '会员选择',
        area: ['825px', '515px'],
        fixed: false,
        btn: ['保存', '返回'],
        content: '/member/admin/member/select.html?id=' + member_id,
        yes: function (index, e) {
          e = e.find('iframe')[0].contentWindow,
          e.getCheckItem(obj => typeof(fn)=='function'&&fn.call(e, obj)),
          parent.layer.close(index)
        }
      });
    }

    that.on('click', function() {
      showPopup(res => {
        let elem, $set;
        Object.keys(mapper).forEach(key => {
          if (typeof(res[key]) != 'undefined') {
            elem = $(mapper[key]);
            if (elem.length > 0) {
              $set = elem.prop('tagName') == 'INPUT' ? 'val' : 'text',
              elem[$set](res[key])
            }
          }
        }),
        member_id = res.member_id,
        that.attr('data-id', member_id)
      });
      return false;
    })
  },
  /**
   * 添加上传组件
   * @param {*} v 
   */
  albumRender(v) {
    let that = $(v), limit = that.data('limit'), value = that.find('input[type="hidden"]').val();
    let template = $("#uploadImage").html();
    let values = value?value.split(','):[];
    let ev = that.data('event');
    limit -= values.length;

    function data() {
      return {
        list: values,
        max: limit
      }
    }

    function render() {
      that.find('.inner').empty(),
      laytpl(template).render(data(), function (html) {
        that.find('.inner').append(html),
        event()
      })
    }

    function trigger() {
      if (ev != undefined) {
        (ev.indexOf('.') != -1 || ev.indexOf('(') != -1) ? eval(ev) : window[ev].call(that[0])
      }
    }

    function event() {
      that.find('.js-add-image').on('click', function() {
        ns.album(res => {
          let d = data();
          res.forEach(v => d.list.values.length<limit&&d.list.push(v.filepath)),
          that.find('input[type="hidden"]').val(d.list.join(',')),
          limit = limit - d.list.length,
          limit < 0 && (limit = 0),
          render(),
          trigger(d.list)
        }, limit)
      }),

      that.find('[layer-src]').on('click', function(){
        let img = this,layout = img.parentNode;
        !layout.id&&(layout.id='img_' + (new Date()).getTime(),layout.setAttribute('id', layout.id)),
        top.layer.photos({
          photos: {
            title: "Photos Demo",
            start: 0,
            data: [{
              pid: 0,
              src: img.src,
              thumb: img.src
            }]
          }, anim: 5
        })
      }),

      that.find('.js-preview').on('click', function() {
        $(this).parent().prev().find("img").trigger('click')
      }),

      that.find('.js-delete').on('click', function() {
				let index = this.getAttribute("data-index"), d = data();
        d.list.splice(index, 1),
        that.find('input[type="hidden"]').val(d.list.join(',')),
        limit += 1,
        render()
      })
    }

    render()
  },

  /**
   * 分配窗口ID
   * @returns void
   */
  register() {
    this.ischild = self.frameElement && self.frameElement.tagName == 'IFRAME' ? 1 : 0;
    if (this.ischild) {
      this.id = self.frameElement.getAttribute("data-id");
      if (!this.id) {
        this.id = (new Date()).getTime(),
        self.frameElement.setAttribute("id", (new Date()).getTime()),
        self.frameElement.setAttribute("name", (new Date()).getTime())
      }
    } else {
      this.id = (new Date()).getTime()
    }
    console.log("注册" + (this.ischild ? '子' : '') + "窗口 " + this.id),
    this.ischild && self.parent.ns.stock.length > 0 && (this.from = self.parent.ns.stock.pop(), console.log("父窗口 " + this.from.id))
  },

  /**
   * 显示组件
   * @returns void
   */
  view() {
    $('.image-uploader').each((i,v) => this.albumRender(v)),
    $('.ns-member-select').each((i,v) => this.memberSelectRender(v))
  },

  /**
   * 剪贴板
   * @returns Promise
   */
  clipboard(elem) {
    layui.config({ base: '/static/admin/modules/' }).use('clipboard', function() {
      layui.clipboard.render({
        elem: elem,
        success() {
          if (typeof(window['clipboard_flag']) != undefined &&  window['clipboard_flag'] == true) {
            // TODO:
          } else {
            window['clipboard_flag'] = true;
            top.layer.msg("已复制", { time: 600, success() {
              setTimeout(() => {
                delete window['clipboard_flag']
              }, 1000)
            } })
          }
        }
      })
    })
  },

  /**
   * 表单初始化
   * @returns void
   */
  init(fn) {
	  layui.use(['laytpl', 'layer', 'util'], () => {
      window.$ = layui.$,
      window.laytpl = layui.laytpl,
      this.register(),
      this.view(),
      typeof(fn) === 'function' && fn()
    })
  },

  /**
   * 引入JS文件
   * @param {*} url 
   */
  requireJs(url) {
    var script = document.createElement("script");
    script.src = url,
    document.body.appendChild(script)
  },

  /**
   * 引入CSS文件
   * @param {*} url 
   */
  requireCss(url) {
    var link = document.createElement('link');
    link.type = 'text/css';
    link.rel = 'stylesheet';
    link.href = url;
    document.body.appendChild(link)
  },

  /**
   * 引用外部插件
   * @param {*} url 
   */
  require(ps) {
    try {
      typeof(window['jQuery'])=='undefined' && (window['jQuery'] = window['$'] = layui.$),
      Object.keys(ps).forEach(k => {
        if (typeof($.fn[k]) == 'undefined') {
          $.fn[k] = true,
          ps[k].forEach(url => {
            let s = url.split('/').pop();
            s.substr(-3) == '.js' ? this.requireJs(url) : s.substr(-3) == 'css' ? this.requireCss(url) : void(0);
          })
        }
      })
    } catch (e) {}
  }
}