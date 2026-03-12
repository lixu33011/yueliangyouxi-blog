/**
 * Simditor v2.3.21
 * http://simditor.tower.im/
 *
 * Released under the MIT license
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define(['jquery', './module'], factory);
  } else if (typeof exports === 'object') {
    module.exports = factory(require('jquery'), require('./module'));
  } else {
    factory(root.jQuery, root.Simditor);
  }
}(this, function ($, Simditor) {
  'use strict';

  var Hotkeys = Simditor.Module.extend({
    name: 'hotkeys',

    defaults: {
      shortcuts: {
        'ctrl+b': 'bold',
        'ctrl+i': 'italic',
        'ctrl+u': 'underline',
        'ctrl+z': 'undo',
        'ctrl+y': 'redo',
        'ctrl+shift+z': 'redo',
        'enter': 'br',
        'shift+enter': 'p',
        'ctrl+enter': 'blockquote',
        'tab': 'indent',
        'shift+tab': 'outdent',
        'ctrl+[': 'outdent',
        'ctrl+]': 'indent',
        'ctrl+1': 'h1',
        'ctrl+2': 'h2',
        'ctrl+3': 'h3',
        'ctrl+4': 'h4',
        'ctrl+5': 'h5',
        'ctrl+6': 'h6',
        'ctrl+shift+s': 'strikethrough',
        'ctrl+shift+c': 'code',
        'ctrl+k': 'link'
      }
    },

    _init: function () {
      this.editor.on('keydown', this._onKeydown.bind(this));
      this._disabledShortcuts = {};
    },

    _onKeydown: function (e) {
      if (this.isDisabled()) {
        return true;
      }

      var keyStr = this._getKeyStr(e),
          shortcut = this.opts.shortcuts[keyStr];

      if (!shortcut || this._disabledShortcuts[keyStr]) {
        return true;
      }

      e.preventDefault();
      e.stopPropagation();

      if ($.isFunction(shortcut)) {
        shortcut.call(this.editor, e);
      } else if ($.isFunction(this.editor[shortcut])) {
        this.editor[shortcut]();
      } else if (this.editor.toolbar) {
        this.editor.toolbar.trigger(shortcut);
      }

      return false;
    },

    _getKeyStr: function (e) {
      var keyParts = [];

      if (e.ctrlKey && !e.metaKey) {
        keyParts.push('ctrl');
      }
      if (e.metaKey && !e.ctrlKey) {
        keyParts.push('meta');
      }
      if (e.altKey) {
        keyParts.push('alt');
      }
      if (e.shiftKey) {
        keyParts.push('shift');
      }

      var keyCode = e.which || e.keyCode,
          keyChar = this._keyCodeToChar(keyCode);

      if (keyChar) {
        keyParts.push(keyChar);
      }

      return keyParts.join('+');
    },

    _keyCodeToChar: function (code) {
      var charMap = {
        8: 'backspace',
        9: 'tab',
        13: 'enter',
        27: 'esc',
        32: 'space',
        37: 'left',
        38: 'up',
        39: 'right',
        40: 'down',
        123: 'f12'
      };

      if (charMap[code]) {
        return charMap[code];
      }

      if (code >= 48 && code <= 57) {
        return String.fromCharCode(code);
      }
      if (code >= 65 && code <= 90) {
        return String.fromCharCode(code).toLowerCase();
      }
      if (code >= 97 && code <= 122) {
        return String.fromCharCode(code);
      }
      if (code === 219) {
        return '[';
      }
      if (code === 221) {
        return ']';
      }

      return '';
    },

    disable: function (shortcut) {
      if (shortcut) {
        this._disabledShortcuts[shortcut] = true;
      } else {
        Simditor.Module.prototype.disable.call(this);
      }
      return this;
    },

    enable: function (shortcut) {
      if (shortcut) {
        delete this._disabledShortcuts[shortcut];
      } else {
        Simditor.Module.prototype.enable.call(this);
        this._disabledShortcuts = {};
      }
      return this;
    },

    destroy: function () {
      this.editor.off('keydown', this._onKeydown);
      Simditor.Module.prototype.destroy.call(this);
    }
  });

  Simditor.Module.register('hotkeys', Hotkeys);

  return Simditor;
}));