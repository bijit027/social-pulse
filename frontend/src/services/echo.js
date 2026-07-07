import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

let echo = null;

if (import.meta.env.VITE_PUSHER_APP_KEY) {
    try {
        echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true
        });
    } catch (error) {
        console.error('Failed to initialize Echo:', error);
    }
} else {
    console.warn('Pusher App Key not found. WebSockets disabled.');
}

export default echo;
