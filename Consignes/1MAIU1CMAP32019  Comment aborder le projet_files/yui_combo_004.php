YUI.add('moodle-core-event', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @module moodle-core-event
 */

var LOGNAME = 'moodle-core-event';

/**
 * List of published global JS events in Moodle. This is a collection
 * of global events that can be subscribed to, or fired from any plugin.
 *
 * @namespace M.core
 * @class event
 */
M.core = M.core || {};

M.core.event = M.core.event || {
    /**
     * This event is triggered when a page has added dynamic nodes to a page
     * that should be processed by the filter system. An example is loading
     * user text that could have equations in it. MathJax can typeset the equations
     * but only if it is notified that there are new nodes in the page that need processing.
     * To trigger this event use M.core.Event.fire(M.core.Event.FILTER_CONTENT_UPDATED, {nodes: list});
     *
     * @event "filter-content-updated"
     * @param nodes {Y.NodeList} List of nodes added to the DOM.
     */
    FILTER_CONTENT_UPDATED: "filter-content-updated",
    /**
     * This event is triggered when an editor has recovered some draft text.
     * It can be used to determine let other sections know that they should reset their
     * form comparison for changes.
     *
     * @event "editor-content-restored"
     */
    EDITOR_CONTENT_RESTORED: "editor-content-restored"
};

M.core.globalEvents = M.core.globalEvents || {
    /**
     * This event is triggered when form has an error
     *
     * @event "form_error"
     * @param formid {string} Id of form with error.
     * @param elementid {string} Id of element with error.
     */
    FORM_ERROR: "form_error",

    /**
     * This event is triggered when the content of a block has changed
     *
     * @event "block_content_updated"
     * @param instanceid ID of the block instance that was updated
     */
    BLOCK_CONTENT_UPDATED: "block_content_updated"
};


var eventDefaultConfig = {
    emitFacade: true,
    defaultFn: function(e) {
        Y.log('Event fired: ' + e.type, 'debug', LOGNAME);
    },
    preventedFn: function(e) {
        Y.log('Event prevented: ' + e.type, 'debug', LOGNAME);
    },
    stoppedFn: function(e) {
        Y.log('Event stopped: ' + e.type, 'debug', LOGNAME);
    }
};

// Publish events with a custom config here.

// Publish all the events with a standard config.
var key;
for (key in M.core.event) {
    if (M.core.event.hasOwnProperty(key) && Y.getEvent(M.core.event[key]) === null) {
        Y.publish(M.core.event[key], eventDefaultConfig);
    }
}

// Publish global events.
for (key in M.core.globalEvents) {
    // Make sure the key exists and that the event has not yet been published. Otherwise, skip publishing.
    if (M.core.globalEvents.hasOwnProperty(key) && Y.Global.getEvent(M.core.globalEvents[key]) === null) {
        Y.Global.publish(M.core.globalEvents[key], Y.merge(eventDefaultConfig, {broadcast: true}));
        Y.log('Global event published: ' + key, 'debug', LOGNAME);
    }
}


}, '@VERSION@', {"requires": ["event-custom"]});
YUI.add('moodle-core-widget-focusafterclose', function (Y, NAME) {

/**
 * Provides support for focusing on different nodes after the Widget is
 * hidden.
 *
 * If the focusOnPreviousTargetAfterHide attribute is true, then the module hooks
 * into the show function for that Widget to try and determine which Node
 * caused the Widget to be shown.
 *
 * Alternatively, the focusAfterHide attribute can be passed a Node.
 *
 * @module moodle-core-widget-focusafterhide
 */

var CAN_RECEIVE_FOCUS_SELECTOR = 'input:not([type="hidden"]), ' +
                                 'a[href], button, textarea, select, ' +
                                '[tabindex], [contenteditable="true"]';

/**
 * Provides support for focusing on different nodes after the Widget is
 * hidden.
 *
 * @class M.core.WidgetFocusAfterHide
 */
function WidgetFocusAfterHide() {
    Y.after(this._bindUIFocusAfterHide, this, 'bindUI');
    if (this.get('rendered')) {
        this._bindUIFocusAfterHide();
    }
}

WidgetFocusAfterHide.ATTRS = {
    /**
     * Whether to focus on the target that caused the Widget to be shown.
     *
     * <em>If this is true, and a valid Node is found, any Node specified to focusAfterHide
     * will be ignored.</em>
     *
     * @attribute focusOnPreviousTargetAfterHide
     * @default false
     * @type boolean
     */
    focusOnPreviousTargetAfterHide: {
        value: false
    },

    /**
     * The Node to focus on after hiding the Widget.
     *
     * <em>Note: If focusOnPreviousTargetAfterHide is true, and a valid Node is found, then this
     * value will be ignored. If it is true and not found, then this value will be used as
     * a fallback.</em>
     *
     * @attribute focusAfterHide
     * @default null
     * @type Node
     */
    focusAfterHide: {
        value: null,
        type: Y.Node
    }
};

WidgetFocusAfterHide.prototype = {
    /**
     * The list of Event Handles which we should cancel when the dialogue is destroyed.
     *
     * @property uiHandleFocusAfterHide
     * @type array
     * @protected
     */
    _uiHandlesFocusAfterHide: [],

    /**
     * A reference to the real show method which is being overwritten.
     *
     * @property _showFocusAfterHide
     * @type function
     * @default null
     * @protected
     */
    _showFocusAfterHide: null,

    /**
     * A reference to the detected previous target.
     *
     * @property _previousTargetFocusAfterHide
     * @type function
     * @default null
     * @protected
     */
    _previousTargetFocusAfterHide: null,

    initializer: function() {

        if (this.get('focusOnPreviousTargetAfterHide') && this.show) {
            // Overwrite the parent method so that we can get the focused
            // target.
            this._showFocusAfterHide = this.show;
            this.show = function(e) {
                this._showFocusAfterHide.apply(this, arguments);

                // We use a property rather than overriding the focusAfterHide parameter in
                // case the target cannot be found at hide time.
                this._previousTargetFocusAfterHide = null;
                if (e && e.currentTarget) {
                    Y.log("Determined a Node which caused the Widget to be shown",
                            'debug', 'moodle-core-widget-focusafterhide');
                    this._previousTargetFocusAfterHide = e.currentTarget;
                }
            };
        }
    },

    destructor: function() {
        new Y.EventHandle(this.uiHandleFocusAfterHide).detach();
    },

    /**
     * Set up the event handling required for this module to work.
     *
     * @method _bindUIFocusAfterHide
     * @private
     */
    _bindUIFocusAfterHide: function() {
        // Detach the old handles first.
        new Y.EventHandle(this.uiHandleFocusAfterHide).detach();
        this.uiHandleFocusAfterHide = [
            this.after('visibleChange', this._afterHostVisibleChangeFocusAfterHide)
        ];
    },

    /**
     * Handle the change in UI visibility.
     *
     * This method changes the focus after the hide has taken place.
     *
     * @method _afterHostVisibleChangeFocusAfterHide
     * @private
     */
    _afterHostVisibleChangeFocusAfterHide: function() {
        if (!this.get('visible')) {
            if (this._attemptFocus(this._previousTargetFocusAfterHide)) {
                Y.log("Focusing on the target automatically determined when the Widget was opened",
                        'debug', 'moodle-core-widget-focusafterhide');

            } else if (this._attemptFocus(this.get('focusAfterHide'))) {
                // Fall back to the focusAfterHide value if one was specified.
                Y.log("Focusing on the target provided to focusAfterHide",
                        'debug', 'moodle-core-widget-focusafterhide');

            } else {
                Y.log("No valid focus target found - not returning focus.",
                        'debug', 'moodle-core-widget-focusafterhide');

            }
        }
    },

    _attemptFocus: function(node) {
        var focusTarget = Y.one(node);
        if (focusTarget) {
            focusTarget = focusTarget.ancestor(CAN_RECEIVE_FOCUS_SELECTOR, true);
            if (focusTarget) {
                focusTarget.focus();
                return true;
            }
        }
        return false;
    }
};

var NS = Y.namespace('M.core');
NS.WidgetFocusAfterHide = WidgetFocusAfterHide;


}, '@VERSION@', {"requires": ["base-build", "widget"]});
/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add('plugin', function (Y, NAME) {

    /**
     * Provides the base Plugin class, which plugin developers should extend, when creating custom plugins
     *
     * @module plugin
     */

    /**
     * The base class for all Plugin instances.
     *
     * @class Plugin.Base
     * @extends Base
     * @param {Object} config Configuration object with property name/value pairs.
     */
    function Plugin(config) {
        if (! (this.hasImpl && this.hasImpl(Y.Plugin.Base)) ) {
            Plugin.superclass.constructor.apply(this, arguments);
        } else {
            Plugin.prototype.initializer.apply(this, arguments);
        }
    }

    /**
     * Object defining the set of attributes supported by the Plugin.Base class
     *
     * @property ATTRS
     * @type Object
     * @static
     */
    Plugin.ATTRS = {

        /**
         * The plugin's host object.
         *
         * @attribute host
         * @writeonce
         * @type Plugin.Host
         */
        host : {
            writeOnce: true
        }
    };

    /**
     * The string identifying the Plugin.Base class. Plugins extending
     * Plugin.Base should set their own NAME value.
     *
     * @property NAME
     * @type String
     * @static
     */
    Plugin.NAME = 'plugin';

    /**
     * The name of the property the the plugin will be attached to
     * when plugged into a Plugin Host. Plugins extending Plugin.Base,
     * should set their own NS value.
     *
     * @property NS
     * @type String
     * @static
     */
    Plugin.NS = 'plugin';

    Y.extend(Plugin, Y.Base, {

        /**
         * The list of event handles for event listeners or AOP injected methods
         * applied by the plugin to the host object.
         *
         * @property _handles
         * @private
         * @type Array
         * @value null
         */
        _handles: null,

        /**
         * Initializer lifecycle implementation.
         *
         * @method initializer
         * @param {Object} config Configuration object with property name/value pairs.
         */
        initializer : function(config) {
            this._handles = [];
        },

        /**
         * Destructor lifecycle implementation.
         *
         * Removes any event listeners or injected methods applied by the Plugin
         *
         * @method destructor
         */
        destructor: function() {
            // remove all handles
            if (this._handles) {
                for (var i = 0, l = this._handles.length; i < l; i++) {
                   this._handles[i].detach();
                }
            }
        },

        /**
         * Listens for the "on" moment of events fired by the host,
         * or injects code "before" a given method on the host.
         *
         * @method doBefore
         *
         * @param strMethod {String} The event to listen for, or method to inject logic before.
         * @param fn {Function} The handler function. For events, the "on" moment listener. For methods, the function to execute before the given method is executed.
         * @param context {Object} An optional context to call the handler with. The default context is the plugin instance.
         * @return handle {EventHandle} The detach handle for the handler.
         */
        doBefore: function(strMethod, fn, context) {
            var host = this.get("host"), handle;

            if (strMethod in host) { // method
                handle = this.beforeHostMethod(strMethod, fn, context);
            } else if (host.on) { // event
                handle = this.onHostEvent(strMethod, fn, context);
            }

            return handle;
        },

        /**
         * Listens for the "after" moment of events fired by the host,
         * or injects code "after" a given method on the host.
         *
         * @method doAfter
         *
         * @param strMethod {String} The event to listen for, or method to inject logic after.
         * @param fn {Function} The handler function. For events, the "after" moment listener. For methods, the function to execute after the given method is executed.
         * @param context {Object} An optional context to call the handler with. The default context is the plugin instance.
         * @return handle {EventHandle} The detach handle for the listener.
         */
        doAfter: function(strMethod, fn, context) {
            var host = this.get("host"), handle;

            if (strMethod in host) { // method
                handle = this.afterHostMethod(strMethod, fn, context);
            } else if (host.after) { // event
                handle = this.afterHostEvent(strMethod, fn, context);
            }

            return handle;
        },

        /**
         * Listens for the "on" moment of events fired by the host object.
         *
         * Listeners attached through this method will be detached when the plugin is unplugged.
         *
         * @method onHostEvent
         * @param {String | Object} type The event type.
         * @param {Function} fn The listener.
         * @param {Object} context The execution context. Defaults to the plugin instance.
         * @return handle {EventHandle} The detach handle for the listener.
         */
        onHostEvent : function(type, fn, context) {
            var handle = this.get("host").on(type, fn, context || this);
            this._handles.push(handle);
            return handle;
        },

        /**
         * Listens for the "on" moment of events fired by the host object one time only.
         * The listener is immediately detached when it is executed.
         *
         * Listeners attached through this method will be detached when the plugin is unplugged.
         *
         * @method onceHostEvent
         * @param {String | Object} type The event type.
         * @param {Function} fn The listener.
         * @param {Object} context The execution context. Defaults to the plugin instance.
         * @return handle {EventHandle} The detach handle for the listener.
         */
        onceHostEvent : function(type, fn, context) {
            var handle = this.get("host").once(type, fn, context || this);
            this._handles.push(handle);
            return handle;
        },

        /**
         * Listens for the "after" moment of events fired by the host object.
         *
         * Listeners attached through this method will be detached when the plugin is unplugged.
         *
         * @method afterHostEvent
         * @param {String | Object} type The event type.
         * @param {Function} fn The listener.
         * @param {Object} context The execution context. Defaults to the plugin instance.
         * @return handle {EventHandle} The detach handle for the listener.
         */
        afterHostEvent : function(type, fn, context) {
            var handle = this.get("host").after(type, fn, context || this);
            this._handles.push(handle);
            return handle;
        },

        /**
         * Listens for the "after" moment of events fired by the host object one time only.
         * The listener is immediately detached when it is executed.
         *
         * Listeners attached through this method will be detached when the plugin is unplugged.
         *
         * @method onceAfterHostEvent
         * @param {String | Object} type The event type.
         * @param {Function} fn The listener.
         * @param {Object} context The execution context. Defaults to the plugin instance.
         * @return handle {EventHandle} The detach handle for the listener.
         */
        onceAfterHostEvent : function(type, fn, context) {
            var handle = this.get("host").onceAfter(type, fn, context || this);
            this._handles.push(handle);
            return handle;
        },

        /**
         * Injects a function to be executed before a given method on host object.
         *
         * The function will be detached when the plugin is unplugged.
         *
         * @method beforeHostMethod
         * @param {String} method The name of the method to inject the function before.
         * @param {Function} fn The function to inject.
         * @param {Object} context The execution context. Defaults to the plugin instance.
         * @return handle {EventHandle} The detach handle for the injected function.
         */
        beforeHostMethod : function(strMethod, fn, context) {
            var handle = Y.Do.before(fn, this.get("host"), strMethod, context || this);
            this._handles.push(handle);
            return handle;
        },

        /**
         * Injects a function to be executed after a given method on host object.
         *
         * The function will be detached when the plugin is unplugged.
         *
         * @method afterHostMethod
         * @param {String} method The name of the method to inject the function after.
         * @param {Function} fn The function to inject.
         * @param {Object} context The execution context. Defaults to the plugin instance.
         * @return handle {EventHandle} The detach handle for the injected function.
         */
        afterHostMethod : function(strMethod, fn, context) {
            var handle = Y.Do.after(fn, this.get("host"), strMethod, context || this);
            this._handles.push(handle);
            return handle;
        },

        toString: function() {
            return this.constructor.NAME + '[' + this.constructor.NS + ']';
        }
    });

    Y.namespace("Plugin").Base = Plugin;


}, '3.17.2', {"requires": ["base-base"]});
YUI.add('moodle-core-lockscroll', function (Y, NAME) {

/**
 * Provides the ability to lock the scroll for a page, allowing nested
 * locking.
 *
 * @module moodle-core-lockscroll
 */

/**
 * Provides the ability to lock the scroll for a page.
 *
 * This is achieved by applying the class 'lockscroll' to the body Node.
 *
 * Nested widgets are also supported and the scroll lock is only removed
 * when the final plugin instance is disabled.
 *
 * @class M.core.LockScroll
 * @extends Plugin.Base
 */
Y.namespace('M.core').LockScroll = Y.Base.create('lockScroll', Y.Plugin.Base, [], {

    /**
     * Whether the LockScroll has been activated.
     *
     * @property _enabled
     * @type Boolean
     * @protected
     */
    _enabled: false,

    /**
     * Handle destruction of the lockScroll instance, including disabling
     * of the current instance.
     *
     * @method destructor
     */
    destructor: function() {
        this.disableScrollLock();
    },

    /**
     * Start locking the page scroll.
     *
     * This is achieved by applying the lockscroll class to the body Node.
     *
     * A count of the total number of active, and enabled, lockscroll instances is also kept on
     * the body to ensure that premature disabling does not occur.
     *
     * @method enableScrollLock
     * @param {Boolean} forceOnSmallWindow Whether to enable the scroll lock, even for small window sizes.
     * @chainable
     */
    enableScrollLock: function(forceOnSmallWindow) {
        if (this.isActive()) {
            Y.log('LockScroll already active. Ignoring enable request', 'warn', 'moodle-core-lockscroll');
            return;
        }

        if (!this.shouldLockScroll(forceOnSmallWindow)) {
            Y.log('Dialogue height greater than window height. Ignoring enable request.', 'warn', 'moodle-core-lockscroll');
            return;
        }

        Y.log('Enabling LockScroll.', 'debug', 'moodle-core-lockscroll');
        this._enabled = true;
        var body = Y.one(Y.config.doc.body);

        // Get width of body before turning on lockscroll.
        var widthBefore = body.getComputedStyle('width');

        // We use a CSS class on the body to handle the actual locking.
        body.addClass('lockscroll');

        // Increase the count of active instances - this is used to ensure that we do not
        // remove the locking when parent windows are still open.
        // Note: We cannot use getData here because data attributes are sandboxed to the instance that created them.
        var currentCount = parseInt(body.getAttribute('data-activeScrollLocks'), 10) || 0,
            newCount = currentCount + 1;
        body.setAttribute('data-activeScrollLocks', newCount);
        Y.log("Setting the activeScrollLocks count from " + currentCount + " to " + newCount,
                'debug', 'moodle-core-lockscroll');

        // When initially enabled, set the body max-width to its current width. This
        // avoids centered elements jumping because the width changes when scrollbars
        // disappear.
        if (currentCount === 0) {
            body.setStyle('maxWidth', widthBefore);
        }

        return this;
    },

    /**
     * Recalculate whether lock scrolling should be on or off.
     *
     * @method shouldLockScroll
     * @param {Boolean} forceOnSmallWindow Whether to enable the scroll lock, even for small window sizes.
     * @return boolean
     */
    shouldLockScroll: function(forceOnSmallWindow) {
        var dialogueHeight = this.get('host').get('boundingBox').get('region').height,
            // Most modern browsers use win.innerHeight, but some older versions of IE use documentElement.clientHeight.
            // We fall back to 0 if neither can be found which has the effect of disabling scroll locking.
            windowHeight = Y.config.win.innerHeight || Y.config.doc.documentElement.clientHeight || 0;

        if (!forceOnSmallWindow && dialogueHeight > (windowHeight - 10)) {
            return false;
        } else {
            return true;
        }
    },

    /**
     * Recalculate whether lock scrolling should be on or off because the size of the dialogue changed.
     *
     * @method updateScrollLock
     * @param {Boolean} forceOnSmallWindow Whether to enable the scroll lock, even for small window sizes.
     * @chainable
     */
    updateScrollLock: function(forceOnSmallWindow) {
        // Both these functions already check if scroll lock is active and do the right thing.
        if (this.shouldLockScroll(forceOnSmallWindow)) {
            this.enableScrollLock(forceOnSmallWindow);
        } else {
            this.disableScrollLock(true);
        }

        return this;
    },

    /**
     * Stop locking the page scroll.
     *
     * The instance may be disabled but the scroll lock not removed if other instances of the
     * plugin are also active.
     *
     * @method disableScrollLock
     * @chainable
     */
    disableScrollLock: function(force) {
        if (this.isActive()) {
            Y.log('Disabling LockScroll.', 'debug', 'moodle-core-lockscroll');
            this._enabled = false;

            var body = Y.one(Y.config.doc.body);

            // Decrease the count of active instances.
            // Note: We cannot use getData here because data attributes are sandboxed to the instance that created them.
            var currentCount = parseInt(body.getAttribute('data-activeScrollLocks'), 10) || 1,
                newCount = currentCount - 1;

            if (force || currentCount === 1) {
                body.removeClass('lockscroll');
                body.setStyle('maxWidth', null);
            }

            body.setAttribute('data-activeScrollLocks', currentCount - 1);
            Y.log("Setting the activeScrollLocks count from " + currentCount + " to " + newCount,
                    'debug', 'moodle-core-lockscroll');
        }

        return this;
    },

    /**
     * Return whether scroll locking is active.
     *
     * @method isActive
     * @return Boolean
     */
    isActive: function() {
        return this._enabled;
    }

}, {
    NS: 'lockScroll',
    ATTRS: {
    }
});


}, '@VERSION@', {"requires": ["plugin", "base-build"]});
YUI.add('moodle-core-notification-dialogue', function (Y, NAME) {

/* eslint-disable no-unused-vars, no-unused-expressions */
var DIALOGUE_PREFIX,
    BASE,
    CONFIRMYES,
    CONFIRMNO,
    TITLE,
    QUESTION,
    CSS;

DIALOGUE_PREFIX = 'moodle-dialogue',
BASE = 'notificationBase',
CONFIRMYES = 'yesLabel',
CONFIRMNO = 'noLabel',
TITLE = 'title',
QUESTION = 'question',
CSS = {
    BASE: 'moodle-dialogue-base',
    WRAP: 'moodle-dialogue-wrap',
    HEADER: 'moodle-dialogue-hd',
    BODY: 'moodle-dialogue-bd',
    CONTENT: 'moodle-dialogue-content',
    FOOTER: 'moodle-dialogue-ft',
    HIDDEN: 'hidden',
    LIGHTBOX: 'moodle-dialogue-lightbox'
};

// Set up the namespace once.
M.core = M.core || {};
/* global DIALOGUE_PREFIX, BASE */

/**
 * The generic dialogue class for use in Moodle.
 *
 * @module moodle-core-notification
 * @submodule moodle-core-notification-dialogue
 */

var DIALOGUE_NAME = 'Moodle dialogue',
    DIALOGUE,
    DIALOGUE_FULLSCREEN_CLASS = DIALOGUE_PREFIX + '-fullscreen',
    DIALOGUE_HIDDEN_CLASS = DIALOGUE_PREFIX + '-hidden',
    DIALOGUE_SELECTOR = ' [role=dialog]',
    MENUBAR_SELECTOR = '[role=menubar]',
    DOT = '.',
    HAS_ZINDEX = 'moodle-has-zindex',
    CAN_RECEIVE_FOCUS_SELECTOR = 'input:not([type="hidden"]), a[href], button, textarea, select, [tabindex]';

/**
 * A re-usable dialogue box with Moodle classes applied.
 *
 * @param {Object} c Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.dialogue
 * @extends Panel
 */
DIALOGUE = function(config) {
    // The code below is a hack to add the custom content node to the DOM, on the fly, per-instantiation and to assign the value
    // of 'srcNode' to this newly created node. Normally (see docs: https://yuilibrary.com/yui/docs/widget/widget-extend.html),
    // this node would be pre-existing in the DOM, and an id string would simply be passed in as a property of the config object
    // during widget instantiation, however, because we're creating it on the fly (and 'config.srcNode' isn't set yet), care must
    // be taken to add it to the DOM and to properly set the value of 'config.srcNode' before calling the parent constructor.
    // Note: additional classes can be added to this content node by setting the 'additionalBaseClass' config property (a string).
    var id = 'moodle-dialogue-' + Y.stamp(this); // Can't use this.get('id') as it's not set at this stage.
    config.notificationBase =
        Y.Node.create('<div class="' + CSS.BASE + '">')
              .append(Y.Node.create('<div id="' + id + '" role="dialog" ' +
                                    'aria-labelledby="' + id + '-header-text" class="' + CSS.WRAP + '"></div>')
              .append(Y.Node.create('<div id="' + id + '-header-text" class="' + CSS.HEADER + ' yui3-widget-hd"></div>'))
              .append(Y.Node.create('<div class="' + CSS.BODY + ' yui3-widget-bd"></div>'))
              .append(Y.Node.create('<div class="' + CSS.FOOTER + ' yui3-widget-ft"></div>')));
    Y.one(document.body).append(config.notificationBase);
    config.srcNode = '#' + id;
    delete config.buttons; // Don't let anyone pass in buttons as we want to control these during init. addButton can be used later.
    DIALOGUE.superclass.constructor.apply(this, [config]);
};
Y.extend(DIALOGUE, Y.Panel, {
    // Window resize event listener.
    _resizeevent: null,
    // Orientation change event listener.
    _orientationevent: null,
    _calculatedzindex: false,

    /**
     * The original position of the dialogue before it was reposition to
     * avoid browser jumping.
     *
     * @property _originalPosition
     * @protected
     * @type Array
     */
    _originalPosition: null,

    /**
     * The list of elements that have been aria hidden when displaying
     * this dialogue.
     *
     * @property _hiddenSiblings
     * @protected
     * @type Array
     */
    _hiddenSiblings: null,

    /**
     * Initialise the dialogue.
     *
     * @method initializer
     */
    initializer: function() {
        var bb;

        if (this.get('closeButton') !== false) {
            // The buttons constructor does not allow custom attributes
            this.get('buttons').header[0].setAttribute('title', this.get('closeButtonTitle'));
        }

        // Initialise the element cache.
        this._hiddenSiblings = [];

        if (this.get('render')) {
            this.render();
        }
        this.after('visibleChange', this.visibilityChanged, this);
        if (this.get('center')) {
            this.centerDialogue();
        }

        if (this.get('modal')) {
            // If we're a modal then make sure our container is ARIA
            // hidden by default. ARIA visibility is managed for modal dialogues.
            this.get(BASE).set('aria-hidden', 'true');
            this.plug(Y.M.core.LockScroll);
        }

        // Workaround upstream YUI bug http://yuilibrary.com/projects/yui3/ticket/2532507
        // and allow setting of z-index in theme.
        bb = this.get('boundingBox');
        bb.addClass(HAS_ZINDEX);

        // Add any additional classes that were specified.
        Y.Array.each(this.get('extraClasses'), bb.addClass, bb);

        if (this.get('visible')) {
            this.applyZIndex();
        }
        // Recalculate the zIndex every time the modal is altered.
        this.on('maskShow', this.applyZIndex);

        this.on('maskShow', function() {
            // When the mask shows, position the boundingBox at the top-left of the window such that when it is
            // focused, the position does not change.
            var w = Y.one(Y.config.win),
                bb = this.get('boundingBox');

            if (!this.get('center')) {
                this._originalPosition = bb.getXY();
            }

            if (bb.getStyle('position') !== 'fixed') {
                // If the boundingBox has been positioned in a fixed manner, then it will not position correctly to scrollTop.
                bb.setStyles({
                    top: w.get('scrollTop'),
                    left: w.get('scrollLeft')
                });
            }
        }, this);

        // Add any additional classes to the content node if required.
        var nBase = this.get('notificationBase');
        var additionalClasses = this.get('additionalBaseClass');
        if (additionalClasses !== '') {
            nBase.addClass(additionalClasses);
        }

        // Remove the dialogue from the DOM when it is destroyed.
        this.after('destroyedChange', function() {
            this.get(BASE).remove(true);
        }, this);
    },

    /**
     * Either set the zindex to the supplied value, or set it to one more than the highest existing
     * dialog in the page.
     *
     * @method applyZIndex
     */
    applyZIndex: function() {
        var highestzindex = 1,
            zindexvalue = 1,
            bb = this.get('boundingBox'),
            ol = this.get('maskNode'),
            zindex = this.get('zIndex');
        if (zindex !== 0 && !this._calculatedzindex) {
            // The zindex was specified so we should use that.
            bb.setStyle('zIndex', zindex);
        } else {
            // Determine the correct zindex by looking at all existing dialogs and menubars in the page.
            Y.all(DIALOGUE_SELECTOR + ', ' + MENUBAR_SELECTOR + ', ' + DOT + HAS_ZINDEX).each(function(node) {
                var zindex = this.findZIndex(node);
                if (zindex > highestzindex) {
                    highestzindex = zindex;
                }
            }, this);
            // Only set the zindex if we found a wrapper.
            zindexvalue = (highestzindex + 1).toString();
            bb.setStyle('zIndex', zindexvalue);
            this.set('zIndex', zindexvalue);
            if (this.get('modal')) {
                ol.setStyle('zIndex', zindexvalue);

                // In IE8, the z-indexes do not take effect properly unless you toggle
                // the lightbox from 'fixed' to 'static' and back. This code does so
                // using the minimum setTimeouts that still actually work.
                if (Y.UA.ie && Y.UA.compareVersions(Y.UA.ie, 9) < 0) {
                    setTimeout(function() {
                        ol.setStyle('position', 'static');
                        setTimeout(function() {
                            ol.setStyle('position', 'fixed');
                        }, 0);
                    }, 0);
                }
            }
            this._calculatedzindex = true;
        }
    },

    /**
     * Finds the zIndex of the given node or its parent.
     *
     * @method findZIndex
     * @param {Node} node The Node to apply the zIndex to.
     * @return {Number} Either the zIndex, or 0 if one was not found.
     */
    findZIndex: function(node) {
        // In most cases the zindex is set on the parent of the dialog.
        var zindex = node.getStyle('zIndex') || node.ancestor().getStyle('zIndex');
        if (zindex) {
            return parseInt(zindex, 10);
        }
        return 0;
    },

    /**
     * Event listener for the visibility changed event.
     *
     * @method visibilityChanged
     * @param {EventFacade} e
     */
    visibilityChanged: function(e) {
        var titlebar, bb;
        if (e.attrName === 'visible') {
            this.get('maskNode').addClass(CSS.LIGHTBOX);
            // Going from visible to hidden.
            if (e.prevVal && !e.newVal) {
                bb = this.get('boundingBox');
                if (this._resizeevent) {
                    this._resizeevent.detach();
                    this._resizeevent = null;
                }
                if (this._orientationevent) {
                    this._orientationevent.detach();
                    this._orientationevent = null;
                }
                bb.detach('key', this.keyDelegation);

                if (this.get('modal')) {
                    // Hide this dialogue from screen readers.
                    this.setAccessibilityHidden();
                }
            }
            // Going from hidden to visible.
            if (!e.prevVal && e.newVal) {
                // This needs to be done each time the dialog is shown as new dialogs may have been opened.
                this.applyZIndex();
                // This needs to be done each time the dialog is shown as the window may have been resized.
                this.makeResponsive();
                if (!this.shouldResizeFullscreen()) {
                    if (this.get('draggable')) {
                        titlebar = '#' + this.get('id') + ' .' + CSS.HEADER;
                        this.plug(Y.Plugin.Drag, {handles: [titlebar]});
                        Y.one(titlebar).setStyle('cursor', 'move');
                    }
                }
                this.keyDelegation();

                // Only do accessibility hiding for modals because the ARIA spec
                // says that all ARIA dialogues should be modal.
                if (this.get('modal')) {
                    // Make this dialogue visible to screen readers.
                    this.setAccessibilityVisible();
                }
            }
            if (this.get('center') && !e.prevVal && e.newVal) {
                this.centerDialogue();
            }
        }
    },
    /**
     * If the responsive attribute is set on the dialog, and the window size is
     * smaller than the responsive width - make the dialog fullscreen.
     *
     * @method makeResponsive
     */
    makeResponsive: function() {
        var bb = this.get('boundingBox');

        if (this.shouldResizeFullscreen()) {
            // Make this dialogue fullscreen on a small screen.
            // Disable the page scrollbars.

            // Size and position the fullscreen dialog.

            bb.addClass(DIALOGUE_FULLSCREEN_CLASS);
            bb.setStyles({'left': null,
                          'top': null,
                          'width': null,
                          'height': null,
                          'right': null,
                          'bottom': null});
        } else {
            if (this.get('responsive')) {
                // We must reset any of the fullscreen changes.
                bb.removeClass(DIALOGUE_FULLSCREEN_CLASS)
                    .setStyles({'width': this.get('width'),
                                'height': this.get('height')});
            }
        }

        // Update Lock scroll if the plugin is present.
        if (this.lockScroll) {
            this.lockScroll.updateScrollLock(this.shouldResizeFullscreen());
        }
    },
    /**
     * Center the dialog on the screen.
     *
     * @method centerDialogue
     */
    centerDialogue: function() {
        var bb = this.get('boundingBox'),
            hidden = bb.hasClass(DIALOGUE_HIDDEN_CLASS),
            x,
            y;

        // Don't adjust the position if we are in full screen mode.
        if (this.shouldResizeFullscreen()) {
            return;
        }
        if (hidden) {
            bb.setStyle('top', '-1000px').removeClass(DIALOGUE_HIDDEN_CLASS);
        }
        x = Math.max(Math.round((bb.get('winWidth') - bb.get('offsetWidth')) / 2), 15);
        y = Math.max(Math.round((bb.get('winHeight') - bb.get('offsetHeight')) / 2), 15) + Y.one(window).get('scrollTop');
        bb.setStyles({'left': x, 'top': y});

        if (hidden) {
            bb.addClass(DIALOGUE_HIDDEN_CLASS);
        }
        this.makeResponsive();
    },
    /**
     * Return whether this dialogue should be fullscreen or not.
     *
     * Responsive attribute must be true and we should not be in an iframe and the screen width should
     * be less than the responsive width.
     *
     * @method shouldResizeFullscreen
     * @return {Boolean}
     */
    shouldResizeFullscreen: function() {
        return (window === window.parent) && this.get('responsive') &&
               Math.floor(Y.one(document.body).get('winWidth')) < this.get('responsiveWidth');
    },

    show: function() {
        var result = null,
            header = this.headerNode,
            content = this.bodyNode,
            focusSelector = this.get('focusOnShowSelector'),
            focusNode = null;

        result = DIALOGUE.superclass.show.call(this);

        if (!this.get('center') && this._originalPosition) {
            // Restore the dialogue position to it's location before it was moved at show time.
            this.get('boundingBox').setXY(this._originalPosition);
        }

        // Try and find a node to focus on using the focusOnShowSelector attribute.
        if (focusSelector !== null) {
            focusNode = this.get('boundingBox').one(focusSelector);
        }
        if (!focusNode) {
            // Fall back to the header or the content if no focus node was found yet.
            if (header && header !== '') {
                focusNode = header;
            } else if (content && content !== '') {
                focusNode = content;
            }
        }
        if (focusNode) {
            focusNode.focus();
        }
        return result;
    },

    hide: function(e) {
        if (e) {
            // If the event was closed by an escape key event, then we need to check that this
            // dialogue is currently focused to prevent closing all dialogues in the stack.
            if (e.type === 'key' && e.keyCode === 27 && !this.get('focused')) {
                return;
            }
        }

        // Unlock scroll if the plugin is present.
        if (this.lockScroll) {
            this.lockScroll.disableScrollLock();
        }

        return DIALOGUE.superclass.hide.call(this, arguments);
    },
    /**
     * Setup key delegation to keep tabbing within the open dialogue.
     *
     * @method keyDelegation
     */
    keyDelegation: function() {
        var bb = this.get('boundingBox');
        bb.delegate('key', function(e) {
            var target = e.target;
            var direction = 'forward';
            if (e.shiftKey) {
                direction = 'backward';
            }
            if (this.trapFocus(target, direction)) {
                e.preventDefault();
            }
        }, 'down:9', CAN_RECEIVE_FOCUS_SELECTOR, this);
    },

    /**
     * Trap the tab focus within the open modal.
     *
     * @method trapFocus
     * @param {string} target the element target
     * @param {string} direction tab key for forward and tab+shift for backward
     * @return {Boolean} The result of the focus action.
     */
    trapFocus: function(target, direction) {
        var bb = this.get('boundingBox'),
            firstitem = bb.one(CAN_RECEIVE_FOCUS_SELECTOR),
            lastitem = bb.all(CAN_RECEIVE_FOCUS_SELECTOR).pop();

        if (target === lastitem && direction === 'forward') { // Tab key.
            return firstitem.focus();
        } else if (target === firstitem && direction === 'backward') {  // Tab+shift key.
            return lastitem.focus();
        }
    },

    /**
     * Sets the appropriate aria attributes on this dialogue and the other
     * elements in the DOM to ensure that screen readers are able to navigate
     * the dialogue popup correctly.
     *
     * @method setAccessibilityVisible
     */
    setAccessibilityVisible: function() {
        // Get the element that contains this dialogue because we need it
        // to filter out from the document.body child elements.
        var container = this.get(BASE);

        // We need to get a list containing each sibling element and the shallowest
        // non-ancestral nodes in the DOM. We can shortcut this a little by leveraging
        // the fact that this dialogue is always appended to the document body therefore
        // it's siblings are the shallowest non-ancestral nodes. If that changes then
        // this code should also be updated.
        Y.one(document.body).get('children').each(function(node) {
            // Skip the element that contains us.
            if (node !== container) {
                var hidden = node.get('aria-hidden');
                // If they are already hidden we can ignore them.
                if (hidden !== 'true') {
                    // Save their current state.
                    node.setData('previous-aria-hidden', hidden);
                    this._hiddenSiblings.push(node);

                    // Hide this node from screen readers.
                    node.set('aria-hidden', 'true');
                }
            }
        }, this);

        // Make us visible to screen readers.
        container.set('aria-hidden', 'false');
    },

    /**
     * Restores the aria visibility on the DOM elements changed when displaying
     * the dialogue popup and makes the dialogue aria hidden to allow screen
     * readers to navigate the main page correctly when the dialogue is closed.
     *
     * @method setAccessibilityHidden
     */
    setAccessibilityHidden: function() {
        var container = this.get(BASE);
        container.set('aria-hidden', 'true');

        // Restore the sibling nodes back to their original values.
        Y.Array.each(this._hiddenSiblings, function(node) {
            var previousValue = node.getData('previous-aria-hidden');
            // If the element didn't previously have an aria-hidden attribute
            // then we can just remove the one we set.
            if (previousValue === null) {
                node.removeAttribute('aria-hidden');
            } else {
                // Otherwise set it back to the old value (which will be false).
                node.set('aria-hidden', previousValue);
            }
        });

        // Clear the cache. No longer need to store these.
        this._hiddenSiblings = [];
    }
}, {
    NAME: DIALOGUE_NAME,
    CSS_PREFIX: DIALOGUE_PREFIX,
    ATTRS: {
        /**
         * Any additional classes to add to the base Node.
         *
         * @attribute additionalBaseClass
         * @type String
         * @default ''
         */
        additionalBaseClass: {
            value: ''
        },

        /**
         * The Notification base Node.
         *
         * @attribute notificationBase
         * @type Node
         */
        notificationBase: {

        },

        /**
         * Whether to display the dialogue modally and with a
         * lightbox style.
         *
         * @attribute lightbox
         * @type Boolean
         * @default true
         * @deprecated Since Moodle 2.7. Please use modal instead.
         */
        lightbox: {
            lazyAdd: false,
            setter: function(value) {
                Y.log("The lightbox attribute of M.core.dialogue has been deprecated since Moodle 2.7, " +
                      "please use the modal attribute instead",
                    'warn', 'moodle-core-notification-dialogue');
                this.set('modal', value);
            }
        },

        /**
         * Whether to display a close button on the dialogue.
         *
         * Note, we do not recommend hiding the close button as this has
         * potential accessibility concerns.
         *
         * @attribute closeButton
         * @type Boolean
         * @default true
         */
        closeButton: {
            validator: Y.Lang.isBoolean,
            value: true
        },

        /**
         * The title for the close button if one is to be shown.
         *
         * @attribute closeButtonTitle
         * @type String
         * @default 'Close'
         */
        closeButtonTitle: {
            validator: Y.Lang.isString,
            value: M.util.get_string('closebuttontitle', 'moodle')
        },

        /**
         * Whether to display the dialogue centrally on the screen.
         *
         * @attribute center
         * @type Boolean
         * @default true
         */
        center: {
            validator: Y.Lang.isBoolean,
            value: true
        },

        /**
         * Whether to make the dialogue movable around the page.
         *
         * @attribute draggable
         * @type Boolean
         * @default false
         */
        draggable: {
            validator: Y.Lang.isBoolean,
            value: false
        },

        /**
         * Used to generate a unique id for the dialogue.
         *
         * @attribute COUNT
         * @type String
         * @default null
         * @writeonce
         */
        COUNT: {
            writeOnce: true,
            valueFn: function() {
                return Y.stamp(this);
            }
        },

        /**
         * Used to disable the fullscreen resizing behaviour if required.
         *
         * @attribute responsive
         * @type Boolean
         * @default true
         */
        responsive: {
            validator: Y.Lang.isBoolean,
            value: true
        },

        /**
         * The width that this dialogue should be resized to fullscreen.
         *
         * @attribute responsiveWidth
         * @type Number
         * @default 768
         */
        responsiveWidth: {
            value: 768
        },

        /**
         * Selector to a node that should recieve focus when this dialogue is shown.
         *
         * The default behaviour is to focus on the header.
         *
         * @attribute focusOnShowSelector
         * @default null
         * @type String
         */
        focusOnShowSelector: {
            value: null
        }
    }
});

Y.Base.modifyAttrs(DIALOGUE, {
    /**
     * String with units, or number, representing the width of the Widget.
     * If a number is provided, the default unit, defined by the Widgets
     * DEF_UNIT, property is used.
     *
     * If a value of 'auto' is used, then an empty String is instead
     * returned.
     *
     * @attribute width
     * @default '400px'
     * @type {String|Number}
     */
    width: {
        value: '400px',
        setter: function(value) {
            if (value === 'auto') {
                return '';
            }
            return value;
        }
    },

    /**
     * Boolean indicating whether or not the Widget is visible.
     *
     * We override this from the default Widget attribute value.
     *
     * @attribute visible
     * @default false
     * @type Boolean
     */
    visible: {
        value: false
    },

    /**
     * A convenience Attribute, which can be used as a shortcut for the
     * `align` Attribute.
     *
     * Note: We override this in Moodle such that it sets a value for the
     * `center` attribute if set. The `centered` will always return false.
     *
     * @attribute centered
     * @type Boolean|Node
     * @default false
     */
    centered: {
        setter: function(value) {
            if (value) {
                this.set('center', true);
            }
            return false;
        }
    },

    /**
     * Boolean determining whether to render the widget during initialisation.
     *
     * We override this to change the default from false to true for the dialogue.
     * We then proceed to early render the dialogue during our initialisation rather than waiting
     * for YUI to render it after that.
     *
     * @attribute render
     * @type Boolean
     * @default true
     */
    render: {
        value: true,
        writeOnce: true
    },

    /**
     * Any additional classes to add to the boundingBox.
     *
     * @attribute extraClasses
     * @type Array
     * @default []
     */
    extraClasses: {
        value: []
    },

    /**
     * Identifier for the widget.
     *
     * @attribute id
     * @type String
     * @default a product of guid().
     * @writeOnce
     */
    id: {
        writeOnce: true,
        valueFn: function() {
            var id = 'moodle-dialogue-' + Y.stamp(this);
            return id;
        }
    },

    /**
     * Collection containing the widget's buttons.
     *
     * @attribute buttons
     * @type Object
     * @default {}
     */
    buttons: {
        getter: Y.WidgetButtons.prototype._getButtons,
        setter: Y.WidgetButtons.prototype._setButtons,
        valueFn: function() {
            if (this.get('closeButton') === false) {
                return null;
            } else {
                return [
                    {
                        section: Y.WidgetStdMod.HEADER,
                        classNames: 'closebutton',
                        action: function() {
                            this.hide();
                        }
                    }
                ];
            }
        }
    }
});

Y.Base.mix(DIALOGUE, [Y.M.core.WidgetFocusAfterHide]);

M.core.dialogue = DIALOGUE;
/* global DIALOGUE_PREFIX */

/**
 * A dialogue type designed to display informative messages to users.
 *
 * @module moodle-core-notification
 */

/**
 * Extends core Dialogue to provide a type of dialogue which can be used
 * for informative message which are modal, and centered.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.notification.info
 * @extends M.core.dialogue
 */
var INFO = function() {
    INFO.superclass.constructor.apply(this, arguments);
};

Y.extend(INFO, M.core.dialogue, {
    initializer: function() {
        this.show();
    }
}, {
    NAME: 'Moodle information dialogue',
    CSS_PREFIX: DIALOGUE_PREFIX
});

Y.Base.modifyAttrs(INFO, {
   /**
    * Whether the widget should be modal or not.
    *
    * We override this to change the default from false to true for a subset of dialogues.
    *
    * @attribute modal
    * @type Boolean
    * @default true
    */
    modal: {
        validator: Y.Lang.isBoolean,
        value: true
    }
});

M.core.notification = M.core.notification || {};
M.core.notification.info = INFO;


}, '@VERSION@', {
    "requires": [
        "base",
        "node",
        "panel",
        "escape",
        "event-key",
        "dd-plugin",
        "moodle-core-widget-focusafterclose",
        "moodle-core-lockscroll"
    ]
});
YUI.add('moodle-core-notification-alert', function (Y, NAME) {

/* eslint-disable no-unused-vars, no-unused-expressions */
var DIALOGUE_PREFIX,
    BASE,
    CONFIRMYES,
    CONFIRMNO,
    TITLE,
    QUESTION,
    CSS;

DIALOGUE_PREFIX = 'moodle-dialogue',
BASE = 'notificationBase',
CONFIRMYES = 'yesLabel',
CONFIRMNO = 'noLabel',
TITLE = 'title',
QUESTION = 'question',
CSS = {
    BASE: 'moodle-dialogue-base',
    WRAP: 'moodle-dialogue-wrap',
    HEADER: 'moodle-dialogue-hd',
    BODY: 'moodle-dialogue-bd',
    CONTENT: 'moodle-dialogue-content',
    FOOTER: 'moodle-dialogue-ft',
    HIDDEN: 'hidden',
    LIGHTBOX: 'moodle-dialogue-lightbox'
};

// Set up the namespace once.
M.core = M.core || {};
/* global BASE, TITLE, CONFIRMYES, DIALOGUE_PREFIX */

/**
 * A dialogue type designed to display an alert to the user.
 *
 * @module moodle-core-notification
 * @submodule moodle-core-notification-alert
 */

var ALERT_NAME = 'Moodle alert',
    ALERT;

/**
 * Extends core Dialogue to show the alert dialogue.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.alert
 * @extends M.core.dialogue
 */
ALERT = function(config) {
    config.closeButton = false;
    ALERT.superclass.constructor.apply(this, [config]);
};
Y.extend(ALERT, M.core.notification.info, {
    /**
     * The list of events to detach when destroying this dialogue.
     *
     * @property _closeEvents
     * @type EventHandle[]
     * @private
     */
    _closeEvents: null,
    initializer: function() {
        this._closeEvents = [];
        this.publish('complete');
        var yes = Y.Node.create('<input type="button" class="btn btn-primary" id="id_yuialertconfirm-' + this.get('COUNT') + '"' +
                                 'value="' + this.get(CONFIRMYES) + '" />'),
            content = Y.Node.create('<div class="confirmation-dialogue"></div>')
                    .append(Y.Node.create('<div class="confirmation-message">' + this.get('message') + '</div>'))
                    .append(Y.Node.create('<div class="confirmation-buttons text-xs-right"></div>')
                            .append(yes));
        this.get(BASE).addClass('moodle-dialogue-confirm');
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);
        this.setStdModContent(Y.WidgetStdMod.HEADER,
                '<h1 id="moodle-dialogue-' + this.get('COUNT') + '-header-text">' + this.get(TITLE) + '</h1>',
                Y.WidgetStdMod.REPLACE);

        this._closeEvents.push(
            Y.on('key', this.submit, window, 'down:13', this),
            yes.on('click', this.submit, this)
        );

        var closeButton = this.get('boundingBox').one('.closebutton');
        if (closeButton) {
            // The close button should act exactly like the 'No' button.
            this._closeEvents.push(
                closeButton.on('click', this.submit, this)
            );
        }
    },
    submit: function() {
        new Y.EventHandle(this._closeEvents).detach();
        this.fire('complete');
        this.hide();
        this.destroy();
    }
}, {
    NAME: ALERT_NAME,
    CSS_PREFIX: DIALOGUE_PREFIX,
    ATTRS: {

        /**
         * The title of the alert.
         *
         * @attribute title
         * @type String
         * @default 'Alert'
         */
        title: {
            validator: Y.Lang.isString,
            value: 'Alert'
        },

        /**
         * The message of the alert.
         *
         * @attribute message
         * @type String
         * @default 'Confirm'
         */
        message: {
            validator: Y.Lang.isString,
            value: 'Confirm'
        },

        /**
         * The button text to use to accept the alert.
         *
         * @attribute yesLabel
         * @type String
         * @default 'Ok'
         */
        yesLabel: {
            validator: Y.Lang.isString,
            setter: function(txt) {
                if (!txt) {
                    txt = 'Ok';
                }
                return txt;
            },
            value: 'Ok'
        }
    }
});

M.core.alert = ALERT;


}, '@VERSION@', {"requires": ["moodle-core-notification-dialogue"]});
YUI.add('moodle-core-notification-exception', function (Y, NAME) {

/* eslint-disable no-unused-vars, no-unused-expressions */
var DIALOGUE_PREFIX,
    BASE,
    CONFIRMYES,
    CONFIRMNO,
    TITLE,
    QUESTION,
    CSS;

DIALOGUE_PREFIX = 'moodle-dialogue',
BASE = 'notificationBase',
CONFIRMYES = 'yesLabel',
CONFIRMNO = 'noLabel',
TITLE = 'title',
QUESTION = 'question',
CSS = {
    BASE: 'moodle-dialogue-base',
    WRAP: 'moodle-dialogue-wrap',
    HEADER: 'moodle-dialogue-hd',
    BODY: 'moodle-dialogue-bd',
    CONTENT: 'moodle-dialogue-content',
    FOOTER: 'moodle-dialogue-ft',
    HIDDEN: 'hidden',
    LIGHTBOX: 'moodle-dialogue-lightbox'
};

// Set up the namespace once.
M.core = M.core || {};
/* global BASE, DIALOGUE_PREFIX */

/**
 * A dialogue type designed to display an appropriate error when a generic
 * javascript error was thrown and caught.
 *
 * @module moodle-core-notification
 * @submodule moodle-core-notification-exception
 */

var EXCEPTION_NAME = 'Moodle exception',
    EXCEPTION;

/**
 * Extends core Dialogue to show the exception dialogue.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.exception
 * @extends M.core.dialogue
 */
EXCEPTION = function(c) {
    var config = Y.mix({}, c);
    config.width = config.width || (M.cfg.developerdebug) ? Math.floor(Y.one(document.body).get('winWidth') / 3) + 'px' : null;
    config.closeButton = true;

    // We need to whitelist some properties which are part of the exception
    // prototype, otherwise AttributeCore filters them during value normalisation.
    var whitelist = [
        'message',
        'name',
        'fileName',
        'lineNumber',
        'stack'
    ];
    Y.Array.each(whitelist, function(k) {
        config[k] = c[k];
    });

    EXCEPTION.superclass.constructor.apply(this, [config]);
};
Y.extend(EXCEPTION, M.core.notification.info, {
    _hideTimeout: null,
    _keypress: null,
    initializer: function(config) {
        var content,
            self = this,
            delay = this.get('hideTimeoutDelay');
        this.get(BASE).addClass('moodle-dialogue-exception');
        this.setStdModContent(Y.WidgetStdMod.HEADER,
                '<h1 id="moodle-dialogue-' + config.COUNT + '-header-text">' + Y.Escape.html(config.name) + '</h1>',
                Y.WidgetStdMod.REPLACE);
        content = Y.Node.create('<div class="moodle-exception" data-rel="fatalerror"></div>')
                .append(Y.Node.create('<div class="moodle-exception-message">' + Y.Escape.html(this.get('message')) + '</div>'))
                .append(Y.Node.create('<div class="moodle-exception-param hidden param-filename"><label>File:</label> ' +
                        Y.Escape.html(this.get('fileName')) + '</div>'))
                .append(Y.Node.create('<div class="moodle-exception-param hidden param-linenumber"><label>Line:</label> ' +
                        Y.Escape.html(this.get('lineNumber')) + '</div>'))
                .append(Y.Node.create('<div class="moodle-exception-param hidden param-stacktrace">' +
                                      '<label>Stack trace:</label> <pre>' +
                        this.get('stack') + '</pre></div>'));
        if (M.cfg.developerdebug) {
            content.all('.moodle-exception-param').removeClass('hidden');
        }
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);

        if (delay) {
            this._hideTimeout = setTimeout(function() {
                self.hide();
            }, delay);
        }
        this.after('visibleChange', this.visibilityChanged, this);
        this._keypress = Y.on('key', this.hide, window, 'down:13,27', this);
        this.centerDialogue();
    },
    visibilityChanged: function(e) {
        if (e.attrName === 'visible' && e.prevVal && !e.newVal) {
            if (this._keypress) {
                this._keypress.detach();
            }
            var self = this;
            setTimeout(function() {
                self.destroy();
            }, 1000);
        }
    }
}, {
    NAME: EXCEPTION_NAME,
    CSS_PREFIX: DIALOGUE_PREFIX,
    ATTRS: {
        /**
         * The message of the alert.
         *
         * @attribute message
         * @type String
         * @default ''
         */
        message: {
            value: ''
        },

        /**
         * The name of the alert.
         *
         * @attribute title
         * @type String
         * @default ''
         */
        name: {
            value: ''
        },

        /**
         * The name of the file where the error was thrown.
         *
         * @attribute fileName
         * @type String
         * @default ''
         */
        fileName: {
            value: ''
        },

        /**
         * The line number where the error was thrown.
         *
         * @attribute lineNumber
         * @type String
         * @default ''
         */
        lineNumber: {
            value: ''
        },

        /**
         * The backtrace from the error
         *
         * @attribute lineNumber
         * @type String
         * @default ''
         */
        stack: {
            setter: function(str) {
                var lines = Y.Escape.html(str).split("\n"),
                    pattern = new RegExp('^(.+)@(' + M.cfg.wwwroot + ')?(.{0,75}).*:(\\d+)$'),
                    i;
                for (i in lines) {
                    lines[i] = lines[i].replace(pattern,
                            "<div class='stacktrace-line'>ln: $4</div>" +
                            "<div class='stacktrace-file'>$3</div>" +
                            "<div class='stacktrace-call'>$1</div>");
                }
                return lines.join("\n");
            },
            value: ''
        },

        /**
         * If set, the dialogue is hidden after the specified timeout period.
         *
         * @attribute hideTimeoutDelay
         * @type Number
         * @default null
         * @optional
         */
        hideTimeoutDelay: {
            validator: Y.Lang.isNumber,
            value: null
        }
    }
});

M.core.exception = EXCEPTION;


}, '@VERSION@', {"requires": ["moodle-core-notification-dialogue"]});
YUI.add('moodle-core-notification-ajaxexception', function (Y, NAME) {

/* eslint-disable no-unused-vars, no-unused-expressions */
var DIALOGUE_PREFIX,
    BASE,
    CONFIRMYES,
    CONFIRMNO,
    TITLE,
    QUESTION,
    CSS;

DIALOGUE_PREFIX = 'moodle-dialogue',
BASE = 'notificationBase',
CONFIRMYES = 'yesLabel',
CONFIRMNO = 'noLabel',
TITLE = 'title',
QUESTION = 'question',
CSS = {
    BASE: 'moodle-dialogue-base',
    WRAP: 'moodle-dialogue-wrap',
    HEADER: 'moodle-dialogue-hd',
    BODY: 'moodle-dialogue-bd',
    CONTENT: 'moodle-dialogue-content',
    FOOTER: 'moodle-dialogue-ft',
    HIDDEN: 'hidden',
    LIGHTBOX: 'moodle-dialogue-lightbox'
};

// Set up the namespace once.
M.core = M.core || {};
/* global BASE, DIALOGUE_PREFIX */

/**
 * A dialogue type designed to display an appropriate error when an error
 * thrown in the Moodle codebase was reported during an AJAX request.
 *
 * @module moodle-core-notification
 * @submodule moodle-core-notification-ajaxexception
 */

var AJAXEXCEPTION_NAME = 'Moodle AJAX exception',
    AJAXEXCEPTION;

/**
 * Extends core Dialogue to show the exception dialogue.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.ajaxException
 * @extends M.core.dialogue
 */
AJAXEXCEPTION = function(config) {
    config.name = config.name || 'Error';
    config.closeButton = true;
    AJAXEXCEPTION.superclass.constructor.apply(this, [config]);
};
Y.extend(AJAXEXCEPTION, M.core.notification.info, {
    _keypress: null,
    initializer: function(config) {
        var content,
            self = this,
            delay = this.get('hideTimeoutDelay');
        this.get(BASE).addClass('moodle-dialogue-exception');
        this.setStdModContent(Y.WidgetStdMod.HEADER,
                '<h1 id="moodle-dialogue-' + this.get('COUNT') + '-header-text">' + Y.Escape.html(config.name) + '</h1>',
                Y.WidgetStdMod.REPLACE);
        content = Y.Node.create('<div class="moodle-ajaxexception" data-rel="fatalerror"></div>')
                .append(Y.Node.create('<div class="moodle-exception-message">' + Y.Escape.html(this.get('error')) + '</div>'))
                .append(Y.Node.create('<div class="moodle-exception-param hidden param-debuginfo"><label>URL:</label> ' +
                        this.get('reproductionlink') + '</div>'))
                .append(Y.Node.create('<div class="moodle-exception-param hidden param-debuginfo"><label>Debug info:</label> ' +
                        Y.Escape.html(this.get('debuginfo')) + '</div>'))
                .append(Y.Node.create('<div class="moodle-exception-param hidden param-stacktrace">' +
                                      '<label>Stack trace:</label> <pre>' +
                        Y.Escape.html(this.get('stacktrace')) + '</pre></div>'));
        if (M.cfg.developerdebug) {
            content.all('.moodle-exception-param').removeClass('hidden');
        }
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);

        if (delay) {
            this._hideTimeout = setTimeout(function() {
                self.hide();
            }, delay);
        }
        this.after('visibleChange', this.visibilityChanged, this);
        this._keypress = Y.on('key', this.hide, window, 'down:13, 27', this);
        this.centerDialogue();
    },
    visibilityChanged: function(e) {
        if (e.attrName === 'visible' && e.prevVal && !e.newVal) {
            var self = this;
            this._keypress.detach();
            setTimeout(function() {
                self.destroy();
            }, 1000);
        }
    }
}, {
    NAME: AJAXEXCEPTION_NAME,
    CSS_PREFIX: DIALOGUE_PREFIX,
    ATTRS: {

        /**
         * The error message given in the exception.
         *
         * @attribute error
         * @type String
         * @default 'Unknown error'
         * @optional
         */
        error: {
            validator: Y.Lang.isString,
            value: M.util.get_string('unknownerror', 'moodle')
        },

        /**
         * Any additional debug information given in the exception.
         *
         * @attribute stacktrace
         * @type String|null
         * @default null
         * @optional
         */
        debuginfo: {
            value: null
        },

        /**
         * The complete stack trace provided in the exception.
         *
         * @attribute stacktrace
         * @type String|null
         * @default null
         * @optional
         */
        stacktrace: {
            value: null
        },

        /**
         * A link which may be used by support staff to replicate the issue.
         *
         * @attribute reproductionlink
         * @type String
         * @default null
         * @optional
         */
        reproductionlink: {
            setter: function(link) {
                if (link !== null) {
                    link = Y.Escape.html(link);
                    link = '<a href="' + link + '">' + link.replace(M.cfg.wwwroot, '') + '</a>';
                }
                return link;
            },
            value: null
        },

        /**
         * If set, the dialogue is hidden after the specified timeout period.
         *
         * @attribute hideTimeoutDelay
         * @type Number
         * @default null
         * @optional
         */
        hideTimeoutDelay: {
            validator: Y.Lang.isNumber,
            value: null
        }
    }
});

M.core.ajaxException = AJAXEXCEPTION;


}, '@VERSION@', {"requires": ["moodle-core-notification-dialogue"]});
YUI.add('moodle-filter_glossary-autolinker', function (Y, NAME) {

var AUTOLINKERNAME = 'Glossary filter autolinker',
    WIDTH = 'width',
    HEIGHT = 'height',
    MENUBAR = 'menubar',
    LOCATION = 'location',
    SCROLLBARS = 'scrollbars',
    RESIZEABLE = 'resizable',
    TOOLBAR = 'toolbar',
    STATUS = 'status',
    DIRECTORIES = 'directories',
    FULLSCREEN = 'fullscreen',
    DEPENDENT = 'dependent',
    AUTOLINKER;

AUTOLINKER = function() {
    AUTOLINKER.superclass.constructor.apply(this, arguments);
};
Y.extend(AUTOLINKER, Y.Base, {
    overlay: null,
    alertpanels: {},
    initializer: function() {
        var self = this;
        require(['core/event'], function(event) {
            Y.delegate('click', function(e) {
                e.preventDefault();

                // display a progress indicator
                var title = '',
                    content = Y.Node.create('<div id="glossaryfilteroverlayprogress">' +
                                            '</div>'),
                    o = new Y.Overlay({
                        headerContent:  title,
                        bodyContent: content
                    }),
                    fullurl,
                    cfg;

                window.require(['core/templates'], function(Templates) {
                    Templates.renderPix('i/loading', 'core').then(function(html) {
                        content.append(html);
                    });
                });

                self.overlay = o;
                o.render(Y.one(document.body));

                // Switch over to the ajax url and fetch the glossary item
                fullurl = this.getAttribute('href').replace('showentry.php', 'showentry_ajax.php');
                cfg = {
                    method: 'get',
                    context: self,
                    on: {
                        success: function(id, o) {
                            this.display_callback(o.responseText, event);
                        },
                        failure: function(id, o) {
                            var debuginfo = o.statusText;
                            if (M.cfg.developerdebug) {
                                o.statusText += ' (' + fullurl + ')';
                            }
                            new M.core.exception({message: debuginfo});
                        }
                    }
                };
                Y.io(fullurl, cfg);

            }, Y.one(document.body), 'a.glossary.autolink.concept');
        });
    },
    /**
     * @method display_callback
     * @param {String} content - Content to display
     * @param {Object} event The amd event module used to fire events for jquery and yui.
     */
    display_callback: function(content, event) {
        var data,
            key,
            alertpanel,
            alertpanelid,
            definition,
            position;
        try {
            data = Y.JSON.parse(content);
            if (data.success) {
                this.overlay.hide(); // hide progress indicator

                for (key in data.entries) {
                    definition = data.entries[key].definition + data.entries[key].attachments;
                    alertpanel = new M.core.alert({title: data.entries[key].concept, draggable: true,
                        message: definition, modal: false, yesLabel: M.util.get_string('ok', 'moodle')});
                    // Notify the filters about the modified nodes.
                    event.notifyFilterContentUpdated(alertpanel.get('boundingBox').getDOMNode());
                    Y.Node.one('#id_yuialertconfirm-' + alertpanel.get('COUNT')).focus();

                    // Register alertpanel for stacking.
                    alertpanelid = '#moodle-dialogue-' + alertpanel.get('COUNT');
                    alertpanel.on('complete', this._deletealertpanel, this, alertpanelid);

                    // We already have some windows opened, so set the right position...
                    if (!Y.Object.isEmpty(this.alertpanels)) {
                        position = this._getLatestWindowPosition();
                        Y.Node.one(alertpanelid).setXY([position[0] + 10, position[1] + 10]);
                    }

                    this.alertpanels[alertpanelid] = Y.Node.one(alertpanelid).getXY();
                }

                return true;
            } else if (data.error) {
                new M.core.ajaxException(data);
            }
        } catch (e) {
            new M.core.exception(e);
        }
        return false;
    },
    _getLatestWindowPosition: function() {
        var lastPosition = [0, 0];
        Y.Object.each(this.alertpanels, function(position) {
            if (position[0] > lastPosition[0]) {
                lastPosition = position;
            }
        });
        return lastPosition;
    },
    _deletealertpanel: function(ev, alertpanelid) {
        delete this.alertpanels[alertpanelid];
    }
}, {
    NAME: AUTOLINKERNAME,
    ATTRS: {
        url: {
            validator: Y.Lang.isString,
            value: M.cfg.wwwroot + '/mod/glossary/showentry.php'
        },
        name: {
            validator: Y.Lang.isString,
            value: 'glossaryconcept'
        },
        options: {
            getter: function() {
                return {
                    width: this.get(WIDTH),
                    height: this.get(HEIGHT),
                    menubar: this.get(MENUBAR),
                    location: this.get(LOCATION),
                    scrollbars: this.get(SCROLLBARS),
                    resizable: this.get(RESIZEABLE),
                    toolbar: this.get(TOOLBAR),
                    status: this.get(STATUS),
                    directories: this.get(DIRECTORIES),
                    fullscreen: this.get(FULLSCREEN),
                    dependent: this.get(DEPENDENT)
                };
            },
            readOnly: true
        },
        width: {value: 600},
        height: {value: 450},
        menubar: {value: false},
        location: {value: false},
        scrollbars: {value: true},
        resizable: {value: true},
        toolbar: {value: true},
        status: {value: true},
        directories: {value: false},
        fullscreen: {value: false},
        dependent: {value: true}
    }
});

M.filter_glossary = M.filter_glossary || {};
M.filter_glossary.init_filter_autolinking = function(config) {
    return new AUTOLINKER(config);
};


}, '@VERSION@', {
    "requires": [
        "base",
        "node",
        "io-base",
        "json-parse",
        "event-delegate",
        "overlay",
        "moodle-core-event",
        "moodle-core-notification-alert",
        "moodle-core-notification-exception",
        "moodle-core-notification-ajaxexception"
    ]
});
