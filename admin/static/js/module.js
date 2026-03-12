/**
 * Simditor v2.3.21
 * http://simditor.tower.im/
 *
 * Released under the MIT license
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define(['jquery'], factory);
  } else if (typeof exports === 'object') {
    module.exports = factory(require('jquery'));
  } else {
    root.Simditor = factory(root.jQuery);
  }
}(this, function ($) {
  'use strict';

  var Simditor = window.Simditor || {};

  // 模块基类
  Simditor.Module = function (editor, options) {
    this.editor = editor;
    this.opts = $.extend({}, this.defaults, options || {});
    this._init();
  };

  $.extend(Simditor.Module.prototype, {
    defaults: {},

    _init: function () {},

    // 销毁模块
    destroy: function () {
      this.editor = null;
      this.opts = null;
    },

    // 触发编辑器事件
    trigger: function (eventName, data) {
      return this.editor.trigger(eventName, data);
    },

    // 监听编辑器事件
    on: function (eventName, handler) {
      this.editor.on(eventName, handler);
      return this;
    },

    // 移除编辑器事件监听
    off: function (eventName, handler) {
      this.editor.off(eventName, handler);
      return this;
    },

    // 获取编辑器的toolbar
    toolbar: function () {
      return this.editor.toolbar;
    },

    // 获取编辑器的textarea/iframe容器
    body: function () {
      return this.editor.body;
    },

    // 获取编辑器的selection对象
    selection: function () {
      return this.editor.selection;
    },

    // 获取编辑器的util工具类
    util: function () {
      return this.editor.util;
    },

    // 禁用模块
    disable: function () {
      this.disabled = true;
      return this;
    },

    // 启用模块
    enable: function () {
      this.disabled = false;
      return this;
    },

    // 判断模块是否禁用
    isDisabled: function () {
      return !!this.disabled;
    }
  });

  // 模块注册方法
  Simditor.Module.register = function (name, moduleClass) {
    if (!moduleClass) { return; }
    if (!moduleClass.prototype || !moduleClass.prototype._init) {
      moduleClass.prototype = $.extend({}, Simditor.Module.prototype, moduleClass.prototype);
    }
    Simditor.modules = Simditor.modules || {};
    Simditor.modules[name] = moduleClass;
  };

  // 模块实例化方法
  Simditor.Module.create = function (name, editor, options) {
    var moduleClass = Simditor.modules[name];
    if (!moduleClass) { return null; }
    return new moduleClass(editor, options);
  };

  return Simditor;
}));