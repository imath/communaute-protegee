// modules are defined as an array
// [ module function, map of requires ]
//
// map of requires is short require name -> numeric require
//
// anything defined in a previous bundle is accessed via the
// orig method which is the require for previous bundles
parcelRequire = (function (modules, cache, entry, globalName) {
  // Save the require from previous bundle to this closure if any
  var previousRequire = typeof parcelRequire === 'function' && parcelRequire;
  var nodeRequire = typeof require === 'function' && require;

  function newRequire(name, jumped) {
    if (!cache[name]) {
      if (!modules[name]) {
        // if we cannot find the module within our internal map or
        // cache jump to the current global require ie. the last bundle
        // that was added to the page.
        var currentRequire = typeof parcelRequire === 'function' && parcelRequire;
        if (!jumped && currentRequire) {
          return currentRequire(name, true);
        }

        // If there are other bundles on this page the require from the
        // previous one is saved to 'previousRequire'. Repeat this as
        // many times as there are bundles until the module is found or
        // we exhaust the require chain.
        if (previousRequire) {
          return previousRequire(name, true);
        }

        // Try the node require function if it exists.
        if (nodeRequire && typeof name === 'string') {
          return nodeRequire(name);
        }

        var err = new Error('Cannot find module \'' + name + '\'');
        err.code = 'MODULE_NOT_FOUND';
        throw err;
      }

      localRequire.resolve = resolve;
      localRequire.cache = {};

      var module = cache[name] = new newRequire.Module(name);

      modules[name][0].call(module.exports, localRequire, module, module.exports, this);
    }

    return cache[name].exports;

    function localRequire(x){
      return newRequire(localRequire.resolve(x));
    }

    function resolve(x){
      return modules[name][1][x] || x;
    }
  }

  function Module(moduleName) {
    this.id = moduleName;
    this.bundle = newRequire;
    this.exports = {};
  }

  newRequire.isParcelRequire = true;
  newRequire.Module = Module;
  newRequire.modules = modules;
  newRequire.cache = cache;
  newRequire.parent = previousRequire;
  newRequire.register = function (id, exports) {
    modules[id] = [function (require, module) {
      module.exports = exports;
    }, {}];
  };

  var error;
  for (var i = 0; i < entry.length; i++) {
    try {
      newRequire(entry[i]);
    } catch (e) {
      // Save first error but execute all entries
      if (!error) {
        error = e;
      }
    }
  }

  if (entry.length) {
    // Expose entry point to Node, AMD or browser globals
    // Based on https://github.com/ForbesLindesay/umd/blob/master/template.js
    var mainExports = newRequire(entry[entry.length - 1]);

    // CommonJS
    if (typeof exports === "object" && typeof module !== "undefined") {
      module.exports = mainExports;

    // RequireJS
    } else if (typeof define === "function" && define.amd) {
     define(function () {
       return mainExports;
     });

    // <script>
    } else if (globalName) {
      this[globalName] = mainExports;
    }
  }

  // Override the current require with this new one
  parcelRequire = newRequire;

  if (error) {
    // throw error from earlier, _after updating parcelRequire_
    throw error;
  }

  return newRequire;
})({"script.js":[function(require,module,exports) {
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * DOM customizations.
 */
var CPCustomize = /*#__PURE__*/function () {
  function CPCustomize(_ref) {
    var fieldKey = _ref.fieldKey;

    _classCallCheck(this, CPCustomize);

    this.fieldKey = fieldKey;
    this.tagsToRemove = ['.field-visibility-settings-notoggle', '.field-visibility-settings-toggle', '.field-visibility-settings'];
    this.blogDetails = null;
  }

  _createClass(CPCustomize, [{
    key: "onFormSubmit",
    value: function onFormSubmit(event) {
      var _this = this;

      var formElements = event.srcElement ? Object.values(event.srcElement) : [];
      var signupEmail = '';
      formElements.forEach(function (formElement) {
        if ('signup_email' === formElement.name || 'privacy_policy_email' === formElement.name) {
          signupEmail = formElement.value;
        }

        if (_this.fieldKey === formElement.name) {
          var dynamicField = event.srcElement.querySelector('[name="' + formElement.name + '"]');

          if (null !== dynamicField) {
            dynamicField.value = signupEmail;
          }
        }
      });
    }
  }, {
    key: "toggleBlogForm",
    value: function toggleBlogForm(event) {
      if (true === event.target.checked) {
        this.blogDetails.style.display = 'block';
      } else {
        this.blogDetails.style.display = 'none';
      }
    }
  }, {
    key: "customizeSignUp",
    value: function customizeSignUp() {
      var bpMainContainer = document.querySelector('#buddypress');
      var signupForm = document.querySelector('form[name="signup_form"]');
      var signupSubmit = null !== signupForm ? signupForm.querySelector('[name="signup_submit"]') : null;
      var extraTplNotices = null !== signupForm ? signupForm.querySelector('#template-notices') : null;
      var privacyPolicy = null !== signupForm ? signupForm.querySelector('.privacy-policy-accept') : null;
      var nouveauRegisterWrapper = document.querySelector('#register-page form[name="signup_form"] .layout-wrap');
      var blogDetails = document.querySelector('#blog-details');
      var blogCheckbox = document.querySelector('[name="signup_with_blog"]');
      var tagsToRemoveCompletely = this.tagsToRemove.slice(0, 3); // Customize Form.

      if (null !== signupForm) {
        var hiddenField = document.createElement('input'); // Adds a dynamic field to check the user is a human.

        hiddenField.setAttribute('type', 'hidden');
        hiddenField.setAttribute('name', this.fieldKey);
        signupForm.appendChild(hiddenField); // Listens to form submit.

        signupForm.addEventListener('submit', this.onFormSubmit.bind(this)); // Removes Legacy Template Pack extra notices container.

        if (null !== extraTplNotices) {
          extraTplNotices.remove();
        } // Moves Nouveau Template Pack Feedback selector into the Form.


        if (null !== nouveauRegisterWrapper) {
          var nouveauFeedback = document.querySelector('#register-page aside.bp-feedback');

          if (null !== nouveauFeedback) {
            nouveauRegisterWrapper.prepend(nouveauFeedback);
          }
        } // Adds extra inputs and labels of the Legacy template pack to the tags to remove.


        if (bpMainContainer && !bpMainContainer.classList.contains('buddypress-wrap')) {
          this.tagsToRemove.push('#signup_password', '#pass-strength-result', 'label[for="signup_password_confirm"]', '#signup_password_confirm');
        } // Removes not needed tags.


        this.tagsToRemove.forEach(function (selector) {
          var extraSelector = signupForm.querySelectorAll(selector);

          if (extraSelector.length) {
            if (-1 === tagsToRemoveCompletely.indexOf(selector)) {
              extraSelector[0].remove();
            } else {
              extraSelector.forEach(function (tag) {
                tag.remove();
              });
            }
          }
        });

        if (null !== blogDetails && null !== blogCheckbox) {
          this.blogDetails = blogDetails;

          if (!blogCheckbox.getAttribute('checked')) {
            this.blogDetails.style.display = 'none';
          }

          blogCheckbox.addEventListener('click', this.toggleBlogForm.bind(this));
        } // Makes sure the Submit button style is consistent with the login form.


        if (null !== privacyPolicy) {
          signupForm.insertBefore(privacyPolicy, signupSubmit.parentNode);
          privacyPolicy.classList.add('register-section');
          privacyPolicy.style.marginTop = '1.5em';
        } // Makes sure the Submit button style is consistent with the login form.


        if (null !== signupSubmit) {
          signupSubmit.setAttribute('id', 'wp-submit');
          signupSubmit.classList.add('button', 'button-primary', 'button-large');
        }
      }
    }
  }, {
    key: "customizeSignupConfirmation",
    value: function customizeSignupConfirmation() {
      var confirmationFeedback = document.querySelector('aside.bp-feedback');
      var nouveauRegisterWrapper = document.querySelector('#register-page form[name="signup_form"] .layout-wrap');
      var extraTplNotices = document.querySelectorAll('form[name="signup_form"] #template-notices');

      if (null !== confirmationFeedback && null !== nouveauRegisterWrapper) {
        nouveauRegisterWrapper.prepend(confirmationFeedback);
      }

      if (null !== extraTplNotices && 2 === extraTplNotices.length) {
        extraTplNotices[0].remove();
        extraTplNotices[1].removeAttribute('id');
        extraTplNotices[1].removeAttribute('role');
        extraTplNotices[1].removeAttribute('aria-atomic');
        extraTplNotices[1].classList.add('register-section');
      }
    }
  }, {
    key: "customizeSignupActivation",
    value: function customizeSignupActivation() {
      var activationPage = document.querySelector('#activate-page');
      var nouveauFeedback = document.querySelector('aside.bp-feedback');
      var activationForm = document.querySelector('#activation-form');
      var activatedFormLink = document.querySelector('body.bp-nouveau .activation-result.successful a');
      var activationSubmit = null !== activationForm ? activationForm.querySelector('#activate [name="submit"]') : null;

      if (null !== activationSubmit) {
        activationSubmit.classList.add('button', 'button-primary', 'button-large');
      }

      if (null !== activatedFormLink) {
        activatedFormLink.classList.add('button', 'button-primary', 'button-large');
      }

      if (null !== activationPage && null !== activationForm) {
        for (var elt in activationPage.children) {
          if (1 === activationPage.children[elt].nodeType && 'activation-form' !== activationPage.children[elt].getAttribute('id') && 'template-notices' !== activationPage.children[elt].getAttribute('id') && activationPage.children[elt] !== nouveauFeedback) {
            activationForm.prepend(activationPage.children[elt]);
          }
        }
      }
    }
  }, {
    key: "start",
    value: function start() {
      var registerPage = document.querySelector('#register');
      var activatePage = document.querySelector('#activate-page');

      if (this.fieldKey) {
        this.customizeSignUp();
      } else if (null !== registerPage && registerPage.classList.contains('completed-confirmation')) {
        this.customizeSignupConfirmation();
      } else if (null !== activatePage) {
        this.customizeSignupActivation();
      }
    }
  }]);

  return CPCustomize;
}();

var settings = window.communauteProtegee && window.communauteProtegee.settings ? communauteProtegee.settings : {};
var cpCustomize = new CPCustomize(settings);

if ('loading' === document.readyState) {
  document.addEventListener('DOMContentLoaded', cpCustomize.start());
} else {
  cpCustomize.start();
}
},{}],"../../node_modules/parcel-bundler/src/builtins/hmr-runtime.js":[function(require,module,exports) {
var global = arguments[3];
var OVERLAY_ID = '__parcel__error__overlay__';
var OldModule = module.bundle.Module;

function Module(moduleName) {
  OldModule.call(this, moduleName);
  this.hot = {
    data: module.bundle.hotData,
    _acceptCallbacks: [],
    _disposeCallbacks: [],
    accept: function (fn) {
      this._acceptCallbacks.push(fn || function () {});
    },
    dispose: function (fn) {
      this._disposeCallbacks.push(fn);
    }
  };
  module.bundle.hotData = null;
}

module.bundle.Module = Module;
var checkedAssets, assetsToAccept;
var parent = module.bundle.parent;

if ((!parent || !parent.isParcelRequire) && typeof WebSocket !== 'undefined') {
  var hostname = "" || location.hostname;
  var protocol = location.protocol === 'https:' ? 'wss' : 'ws';
  var ws = new WebSocket(protocol + '://' + hostname + ':' + "54805" + '/');

  ws.onmessage = function (event) {
    checkedAssets = {};
    assetsToAccept = [];
    var data = JSON.parse(event.data);

    if (data.type === 'update') {
      var handled = false;
      data.assets.forEach(function (asset) {
        if (!asset.isNew) {
          var didAccept = hmrAcceptCheck(global.parcelRequire, asset.id);

          if (didAccept) {
            handled = true;
          }
        }
      }); // Enable HMR for CSS by default.

      handled = handled || data.assets.every(function (asset) {
        return asset.type === 'css' && asset.generated.js;
      });

      if (handled) {
        console.clear();
        data.assets.forEach(function (asset) {
          hmrApply(global.parcelRequire, asset);
        });
        assetsToAccept.forEach(function (v) {
          hmrAcceptRun(v[0], v[1]);
        });
      } else if (location.reload) {
        // `location` global exists in a web worker context but lacks `.reload()` function.
        location.reload();
      }
    }

    if (data.type === 'reload') {
      ws.close();

      ws.onclose = function () {
        location.reload();
      };
    }

    if (data.type === 'error-resolved') {
      console.log('[parcel] ✨ Error resolved');
      removeErrorOverlay();
    }

    if (data.type === 'error') {
      console.error('[parcel] 🚨  ' + data.error.message + '\n' + data.error.stack);
      removeErrorOverlay();
      var overlay = createErrorOverlay(data);
      document.body.appendChild(overlay);
    }
  };
}

function removeErrorOverlay() {
  var overlay = document.getElementById(OVERLAY_ID);

  if (overlay) {
    overlay.remove();
  }
}

function createErrorOverlay(data) {
  var overlay = document.createElement('div');
  overlay.id = OVERLAY_ID; // html encode message and stack trace

  var message = document.createElement('div');
  var stackTrace = document.createElement('pre');
  message.innerText = data.error.message;
  stackTrace.innerText = data.error.stack;
  overlay.innerHTML = '<div style="background: black; font-size: 16px; color: white; position: fixed; height: 100%; width: 100%; top: 0px; left: 0px; padding: 30px; opacity: 0.85; font-family: Menlo, Consolas, monospace; z-index: 9999;">' + '<span style="background: red; padding: 2px 4px; border-radius: 2px;">ERROR</span>' + '<span style="top: 2px; margin-left: 5px; position: relative;">🚨</span>' + '<div style="font-size: 18px; font-weight: bold; margin-top: 20px;">' + message.innerHTML + '</div>' + '<pre>' + stackTrace.innerHTML + '</pre>' + '</div>';
  return overlay;
}

function getParents(bundle, id) {
  var modules = bundle.modules;

  if (!modules) {
    return [];
  }

  var parents = [];
  var k, d, dep;

  for (k in modules) {
    for (d in modules[k][1]) {
      dep = modules[k][1][d];

      if (dep === id || Array.isArray(dep) && dep[dep.length - 1] === id) {
        parents.push(k);
      }
    }
  }

  if (bundle.parent) {
    parents = parents.concat(getParents(bundle.parent, id));
  }

  return parents;
}

function hmrApply(bundle, asset) {
  var modules = bundle.modules;

  if (!modules) {
    return;
  }

  if (modules[asset.id] || !bundle.parent) {
    var fn = new Function('require', 'module', 'exports', asset.generated.js);
    asset.isNew = !modules[asset.id];
    modules[asset.id] = [fn, asset.deps];
  } else if (bundle.parent) {
    hmrApply(bundle.parent, asset);
  }
}

function hmrAcceptCheck(bundle, id) {
  var modules = bundle.modules;

  if (!modules) {
    return;
  }

  if (!modules[id] && bundle.parent) {
    return hmrAcceptCheck(bundle.parent, id);
  }

  if (checkedAssets[id]) {
    return;
  }

  checkedAssets[id] = true;
  var cached = bundle.cache[id];
  assetsToAccept.push([bundle, id]);

  if (cached && cached.hot && cached.hot._acceptCallbacks.length) {
    return true;
  }

  return getParents(global.parcelRequire, id).some(function (id) {
    return hmrAcceptCheck(global.parcelRequire, id);
  });
}

function hmrAcceptRun(bundle, id) {
  var cached = bundle.cache[id];
  bundle.hotData = {};

  if (cached) {
    cached.hot.data = bundle.hotData;
  }

  if (cached && cached.hot && cached.hot._disposeCallbacks.length) {
    cached.hot._disposeCallbacks.forEach(function (cb) {
      cb(bundle.hotData);
    });
  }

  delete bundle.cache[id];
  bundle(id);
  cached = bundle.cache[id];

  if (cached && cached.hot && cached.hot._acceptCallbacks.length) {
    cached.hot._acceptCallbacks.forEach(function (cb) {
      cb();
    });

    return true;
  }
}
},{}]},{},["../../node_modules/parcel-bundler/src/builtins/hmr-runtime.js","script.js"], null)