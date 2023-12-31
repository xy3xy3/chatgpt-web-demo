// 添加请求拦截器
axios.interceptors.request.use(
  function (config) {
    config["load"] = layer.load({
      type: 2,
      shadeClose: false,
    });
    return config;
  },
  function (error) {
    return Promise.reject(error);
  }
);
// 添加响应拦截器
axios.interceptors.response.use(
  function (response) {
    layer.close(response.config.load);
    if (response.data.status === 419) {
      setTimeout(function () {
        window.location.reload();
      }, 1000);
    }
    return response.data;
  },
  function (error) {
    layer.close(error.response.config.load);
    var data = error.response.data;
    if (
      (typeof data === "undefined" ? "undefined" : _typeof(data)) === "object"
    ) {
      if (data.status === 419) {
        setTimeout(function () {
          window.location.reload();
        }, 1000);
      }
      return data;
    } else {
      return { status: 500, message: error.message };
    }
  }
);

Vue.use({
  install(Vue) {
    // 将Layer.js绑定到Vue实例的$layer属性上
    Vue.prototype.$layer = layer;
    //绑定axios
    Vue.prototype.$axios = axios;
  },
});
// 定义$.get函数
Vue.prototype.$get = function (url, params) {
  return this.$axios.get(url, { params });
};

// 定义$.post函数
Vue.prototype.$post = function (url, data) {
  return this.$axios.post(url, data);
};

//定义消息函数
Vue.prototype.$message = function (message, type) {
  if (type === "success") {
    layer.msg(message, { icon: 1 });
  } else {
    layer.alert(message);
  }
};
//定义stream流
Vue.prototype.$stream = function (url, postData, callback) {
  fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(postData),
  })
    .then((response) => {
      const reader = response.body.getReader();

      function read() {
        return reader.read().then(({ done, value }) => {
          if (done) {
            console.log("Stream complete");
            return;
          }
          const data = new TextDecoder().decode(value);

          // 处理接收到的数据
          if (callback != undefined) {
            callback(data);
          }
          // 继续读取下一块数据
          return read();
        });
      }
      return read();
    })
    .catch((error) => {
      console.error("Error:", error);
    });
};
// select 插件
Vue.component('vm-select', {
  props : ['options', 'value', 'multiple', 'method', 'load', 'index', 'childidx'],
  template : "<select :multiple='multiple' class='form-control selectpicker' data-live-search='true' title='请选择' data-live-search-placeholder='搜索'><option :value='option.value' v-for='option in options'>{{ option.label }}</option></select>",
  mounted : function () {
      var vm = this;
      $(this.$el).selectpicker('val', this.value != null ? this.value : null);
      $(this.$el).on('changed.bs.select', function () {
          vm.$emit('input', $(this).val());
          if (typeof(vm.method) != 'undefined') {
              vm.method(vm.index, vm.childidx, this.value);
          }
      });
      $(this.$el).on('show.bs.select', function () {
          if (typeof(vm.load) != 'undefined') {
              vm.load(vm.index, vm.childidx);
          }
      });
  },
  updated : function () {
      $(this.$el).selectpicker('refresh');
  },
  destroyed : function () {
      $(this.$el).selectpicker('destroy');
  }
});