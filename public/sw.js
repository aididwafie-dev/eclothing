/*
 * PLAS service worker.
 *
 * Deliberately conservative: pages are never cached. Every screen in this app
 * is behind a session and shows one member's (or one admin's) data, so a
 * cached page could be served to whoever opens the app next on a shared
 * handset. Only public static assets are cached; page requests go to the
 * network and fall back to the offline card when there is none.
 *
 * Bump VERSION to retire old caches on the next visit.
 */
const VERSION = 'v1';
const STATIC_CACHE = 'plas-static-' + VERSION;
const OFFLINE_URL = '/offline.html';

// Best-effort: a missing entry must not fail the install, so each is added
// on its own rather than through cache.addAll.
const PRECACHE = [
	OFFLINE_URL,
	'/front_end/css/style.css',
	'/front_end/bootstrap-3.3.7-dist/css/bootstrap.min.css',
	'/front_end/font-awesome-4.7.0/css/font-awesome.min.css',
	'/front_end/icons/icon-192.png',
	'/front_end/icons/icon-512.png'
];

self.addEventListener('install', function (event) {
	event.waitUntil(
		caches.open(STATIC_CACHE).then(function (cache) {
			return Promise.all(PRECACHE.map(function (url) {
				return cache.add(url).catch(function () { /* keep going */ });
			}));
		}).then(function () {
			return self.skipWaiting();
		})
	);
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches.keys().then(function (keys) {
			return Promise.all(keys.map(function (key) {
				return key === STATIC_CACHE ? null : caches.delete(key);
			}));
		}).then(function () {
			return self.clients.claim();
		})
	);
});

function isCacheableAsset(url) {
	return url.pathname.indexOf('/front_end/') === 0
		|| url.pathname.indexOf('/uploads/') === 0
		|| url.pathname === '/manifest.webmanifest';
}

function cacheFirst(request) {
	return caches.match(request).then(function (cached) {
		if (cached) {
			return cached;
		}

		return fetch(request).then(function (response) {
			// Only store a complete same-origin response; an opaque or partial
			// one would poison the cache.
			if (response && response.status === 200 && response.type === 'basic') {
				var copy = response.clone();
				caches.open(STATIC_CACHE).then(function (cache) {
					cache.put(request, copy);
				});
			}

			return response;
		});
	});
}

self.addEventListener('fetch', function (event) {
	var request = event.request;

	// Anything that changes state (checkout, cart, login) must always reach
	// the server untouched.
	if (request.method !== 'GET') {
		return;
	}

	var url = new URL(request.url);
	if (url.origin !== self.location.origin) {
		return;
	}

	if (isCacheableAsset(url)) {
		event.respondWith(cacheFirst(request));
		return;
	}

	if (request.mode === 'navigate') {
		event.respondWith(
			fetch(request).catch(function () {
				return caches.match(OFFLINE_URL);
			})
		);
	}
});
