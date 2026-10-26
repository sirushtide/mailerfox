/**
 * MailPurse Visitor Tracker
 * Lightweight client-side tracker for page views, button clicks, and dwell time.
 * No dependencies. ~2KB.
 */
(function () {
    'use strict';

    var SESSION_KEY = 'mp_vt_uid';
    var ENDPOINT_EVENT = '/visitor-tracking/event';
    var ENDPOINT_DWELL = '/visitor-tracking/dwell';

    function getSessionUid() {
        try {
            var uid = localStorage.getItem(SESSION_KEY);
            if (uid && uid.length === 36) {
                return uid;
            }
            uid = (crypto && crypto.randomUUID) ? crypto.randomUUID() : generateUuid();
            localStorage.setItem(SESSION_KEY, uid);
            return uid;
        } catch (e) {
            return '';
        }
    }

    function generateUuid() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function sendBeacon(url, payload) {
        var body = JSON.stringify(payload);
        try {
            if (navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(url, blob)) {
                    return;
                }
            }
        } catch (e) {}
        try {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Visitor-Session-Uid': payload.session_uid || '' },
                body: body,
                keepalive: true,
            }).catch(function () {});
        } catch (e) {}
    }

    function sendFetch(url, payload) {
        try {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Visitor-Session-Uid': payload.session_uid || '' },
                body: JSON.stringify(payload),
                keepalive: true,
            }).catch(function () {});
        } catch (e) {}
    }

    var sessionUid = getSessionUid();
    var pageEnterTime = Date.now();
    var currentUrl = window.location.href;

    // Track clicks on [data-track] elements
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-track]');
        if (!el) return;

        var trackId = el.getAttribute('data-track') || '';
        var label = el.getAttribute('data-track-label') || el.textContent.trim().substring(0, 200) || '';
        var selector = '';
        if (el.id) {
            selector = '#' + el.id;
        } else if (el.className && typeof el.className === 'string') {
            selector = '.' + el.className.split(/\s+/).filter(Boolean).join('.');
        }

        sendFetch(ENDPOINT_EVENT, {
            type: 'click',
            track_id: trackId,
            label: label,
            selector: selector,
            url: window.location.href,
            meta: {},
            session_uid: sessionUid,
        });
    }, true);

    // Track dwell time on page leave
    function sendDwell() {
        var dwellSeconds = Math.round((Date.now() - pageEnterTime) / 1000);
        if (dwellSeconds < 1) return;

        sendBeacon(ENDPOINT_DWELL, {
            url: currentUrl,
            dwell_seconds: dwellSeconds,
            session_uid: sessionUid,
        });
    }

    // Send dwell on page hide/unload
    window.addEventListener('pagehide', sendDwell);
    window.addEventListener('beforeunload', sendDwell);

    // Reset timer when returning to page (bfcache)
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            pageEnterTime = Date.now();
        }
    });
})();
