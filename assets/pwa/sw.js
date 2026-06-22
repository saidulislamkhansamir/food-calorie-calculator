// Minimal service worker — required for PWA install prompt.
// No offline caching — just fetch from network.
self.addEventListener( 'fetch', function ( e ) {
	e.respondWith( fetch( e.request ) );
} );
