const API_URL = 'http://localhost/calendify/api';

export const eventService = {
    async getEvents() {
        const response = await fetch(`${API_URL}/events.php`);
        return await response.json();
    },

    async createEvent(event) {
        const response = await fetch(`${API_URL}/events.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(event)
        });
        return await response.json();
    },

    async updateEvent(event) {
        const response = await fetch(`${API_URL}/events.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(event)
        });
        return await response.json();
    },

    async deleteEvent(id) {
        const response = await fetch(`${API_URL}/events.php?id=${id}`, {
            method: 'DELETE'
        });
        return await response.json();
    }
};