<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, fly } from 'svelte/transition';
  import { cubicOut } from 'svelte/easing';

  interface Event {
    id: number;
    title: string;
    date: Date;
  }

  interface Notification {
    id: number;
    message: string;
    type: 'add' | 'edit' | 'delete';
    created_at: string;
  }

  let currentDate: Date = new Date();
  let selectedDate: Date = new Date();
  let events: Event[] = [];
  let newEventTitle: string = '';
  let editingEvent: Event | null = null;
  let showModal: boolean = false;
  let showEventList: boolean = false;
  let showNotification: boolean = false;
  let notificationMessage: string = '';
  let notifications: Notification[] = [];
  let showNotificationPanel: boolean = false;
  let upcomingEvents: Event[] = [];

  const API_URL = 'http://localhost/calendify/api/events.php';

  onMount(async () => {
    await fetchEvents();
    await fetchNotifications();
    checkUpcomingEvents();
  });

  async function fetchEvents() {
    try {
      const response = await fetch(API_URL);
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to fetch events');
      }
      const data = await response.json();
      events = data.map((event: any): Event => ({
        id: event.id,
        title: event.title,
        date: new Date(event.date)
      }));
    } catch (error: unknown) {
      console.error('Error fetching events:', error);
      showNotificationAlert(`Failed to fetch events: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  async function fetchNotifications() {
    try {
      const response = await fetch(`${API_URL}?type=notifications`);
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to fetch notifications');
      }
      const data = await response.json();
      notifications = data.map((notification: any): Notification => ({
        id: notification.id,
        message: notification.message,
        type: notification.type as 'add' | 'edit' | 'delete',
        created_at: notification.created_at
      }));
    } catch (error: unknown) {
      console.error('Error fetching notifications:', error);
      showNotificationAlert(`Failed to fetch notifications: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  function getDaysInMonth(year: number, month: number): number {
    return new Date(year, month + 1, 0).getDate();
  }

  function getFirstDayOfMonth(year: number, month: number): number {
    return new Date(year, month, 1).getDay();
  }

  $: daysInMonth = getDaysInMonth(currentDate.getFullYear(), currentDate.getMonth());
  $: firstDayOfMonth = getFirstDayOfMonth(currentDate.getFullYear(), currentDate.getMonth());

  function previousMonth(): void {
    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
  }

  function nextMonth(): void {
    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
  }

  function selectDate(day: number): void {
    selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
  }

  async function addEvent(): Promise<void> {
    if (newEventTitle.trim()) {
      try {
        const response = await fetch(API_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            title: newEventTitle,
            date: selectedDate.toISOString().split('T')[0]
          }),
        });
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to add event');
        }

        const data = await response.json();
        const newEvent: Event = {
          id: data.id,
          title: newEventTitle,
          date: selectedDate
        };
        events = [...events, newEvent];
        newEventTitle = '';
        showModal = false;
        showNotificationAlert('Event added successfully');
        await fetchNotifications();
        checkUpcomingEvents();
      } catch (error: unknown) {
        console.error('Error adding event:', error);
        showNotificationAlert(`Failed to add event: ${error instanceof Error ? error.message : 'Unknown error'}`);
      }
    }
  }

  function editEvent(event: Event): void {
    editingEvent = { ...event };
    newEventTitle = event.title;
    showModal = true;
  }

  async function updateEvent(): Promise<void> {
    if (!editingEvent || !newEventTitle.trim()) return;
    try {
      const response = await fetch(API_URL, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          id: editingEvent.id,
          title: newEventTitle,
          date: editingEvent.date.toISOString().split('T')[0]
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to update event');
      }

      events = events.map(e => e.id === (editingEvent?.id ?? -1) ? { ...e, title: newEventTitle } : e);
      newEventTitle = '';
      editingEvent = null;
      showModal = false;
      showNotificationAlert('Event updated successfully');
      await fetchNotifications();
      checkUpcomingEvents();
    } catch (error: unknown) {
      console.error('Error updating event:', error);
      showNotificationAlert(`Failed to update event: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  async function deleteEvent(id: number): Promise<void> {
    try {
      const response = await fetch(`${API_URL}?id=${id}`, {
        method: 'DELETE',
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Failed to delete event');
      }

      events = events.filter(e => e.id !== id);
      showNotificationAlert('Event deleted successfully');
      await fetchNotifications();
      checkUpcomingEvents();
    } catch (error: unknown) {
      console.error('Error deleting event:', error);
      showNotificationAlert(`Failed to delete event: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  function showNotificationAlert(message: string): void {
    notificationMessage = message;
    showNotification = true;
    setTimeout(() => {
      showNotification = false;
    }, 3000);
  }

  function checkUpcomingEvents(): void {
    const today = new Date();
    const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
    upcomingEvents = events.filter(event => {
      const eventDate = new Date(event.date);
      return eventDate >= today && eventDate <= nextWeek;
    });
  }

  $: selectedDateEvents = events.filter(
    (event: Event) => event.date.toDateString() === selectedDate.toDateString()
  );

  function formatDate(date: Date): string {
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  function formatNotificationDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric', 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  }

  function NotificationIcon(): string {
    return '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>';
  }
</script>

<main class="min-h-screen bg-gradient-to-br from-emerald-400 via-cyan-500 to-blue-500 text-white p-4">
  <div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-yellow-500">
        Calendify
      </h1>
      <div class="space-x-4 flex items-center">
        <button
          on:click={() => showEventList = !showEventList}
          class="px-6 py-2 bg-white bg-opacity-20 rounded-full text-white font-semibold hover:bg-opacity-30 transition-all duration-300"
        >
          {showEventList ? 'Hide' : 'Show'} Event List
        </button>
        <button
          on:click={() => showNotificationPanel = !showNotificationPanel}
          class="relative p-2 bg-white bg-opacity-20 rounded-full text-white hover:bg-opacity-30 transition-all duration-300"
          aria-label="Show notifications and upcoming events"
        >
          {@html NotificationIcon()}
          {#if notifications.length > 0 || upcomingEvents.length > 0}
            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
              {notifications.length + upcomingEvents.length}
            </span>
          {/if}
        </button>
        <a
          href="/"
          class="px-6 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full text-white font-semibold hover:from-yellow-500 hover:to-orange-600 transition-all duration-300"
        >
          Back to Home
        </a>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
      <div class="flex-1 bg-white bg-opacity-10 backdrop-blur-md rounded-2xl shadow-xl p-6">
        <div class="flex justify-between items-center mb-4">
          <button on:click={previousMonth} class="text-white hover:text-yellow-200 transition-colors">
            &lt; Previous
          </button>
          <h2 class="text-2xl font-semibold">
            {currentDate.toLocaleString('default', { month: 'long', year: 'numeric' })}
          </h2>
          <button on:click={nextMonth} class="text-white hover:text-yellow-200 transition-colors">
            Next &gt;
          </button>
        </div>
        <div class="grid grid-cols-7 gap-2">
          {#each ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as day}
            <div class="text-center font-semibold">{day}</div>
          {/each}
          {#each Array(firstDayOfMonth).fill(null) as _}
            <div></div>
          {/each}
          {#each Array(daysInMonth).fill(null).map((_, i) => i + 1) as day}
            <button
              class="p-2 text-center rounded hover:bg-white hover:bg-opacity-20 transition-colors"
              class:bg-yellow-300={selectedDate.getDate() === day && 
                                   selectedDate.getMonth() === currentDate.getMonth() && 
                                   selectedDate.getFullYear() === currentDate.getFullYear()}
              class:text-blue-900={selectedDate.getDate() === day && 
                                   selectedDate.getMonth() === currentDate.getMonth() && 
                                   selectedDate.getFullYear() === currentDate.getFullYear()}
              on:click={() => selectDate(day)}
            >
              {day}
            </button>
          {/each}
        </div>
      </div>

      <div class="flex-1 bg-white bg-opacity-10 backdrop-blur-md rounded-2xl shadow-xl p-6">
        <h3 class="text-2xl font-semibold mb-4">Events for {formatDate(selectedDate)}</h3>
        {#if selectedDateEvents.length > 0}
          <ul class="space-y-2">
            {#each selectedDateEvents as event (event.id)}
              <li in:fade={{ duration: 300 }} out:fade={{ duration: 200 }} class="flex justify-between items-center p-3 bg-white bg-opacity-20 rounded-lg">
                <span>{event.title}</span>
                <div>
                  <button 
                    on:click={() => editEvent(event)}
                    class="text-yellow-200 hover:text-yellow-400 transition-colors mr-2"
                  >
                    Edit
                  </button>
                  <button 
                    on:click={() => deleteEvent(event.id)}
                    class="text-red-300 hover:text-red-500 transition-colors"
                  >
                    Delete
                  </button>
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="text-gray-200">No events for this date.</p>
        {/if}
        <button
          on:click={() => { editingEvent = null; showModal = true; }}
          class="mt-4 px-6 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full text-white font-semibold hover:from-yellow-500 hover:to-orange-600 transition-all duration-300"
        >
          Add Event
        </button>
      </div>
    </div>

    {#if showEventList}
      <div in:fly={{ y: 50, duration: 500, easing: cubicOut }} out:fade={{ duration: 300 }} class="mt-8 bg-white bg-opacity-10 backdrop-blur-md rounded-2xl shadow-xl p-6">
        <h3 class="text-2xl font-semibold mb-4">All Events</h3>
        {#if events.length > 0}
          <ul class="space-y-2">
            {#each events.sort((a, b) => a.date.getTime() - b.date.getTime()) as event (event.id)}
              <li class="flex justify-between items-center p-3 bg-white bg-opacity-20 rounded-lg">
                <span>{event.title} - {formatDate(event.date)}</span>
                <div>
                  <button 
                    on:click={() => editEvent(event)}
                    class="text-yellow-200 hover:text-yellow-400 transition-colors mr-2"
                  >
                    Edit
                  </button>
                  <button 
                    on:click={() => deleteEvent(event.id)}
                    class="text-red-300 hover:text-red-500 transition-colors"
                  >
                    Delete
                  </button>
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="text-gray-200">No events scheduled.</p>
        {/if}
      </div>
    {/if}

    {#if showNotificationPanel}
      <div in:fly={{ y: 50, duration: 500, easing: cubicOut }} out:fade={{ duration: 300 }} class="mt-8 bg-white bg-opacity-10 backdrop-blur-md rounded-2xl shadow-xl p-6">
        <h3 class="text-2xl font-semibold mb-4">Notifications and Upcoming Events</h3>
        {#if notifications.length > 0 || upcomingEvents.length > 0}
          <ul class="space-y-2">
            {#each notifications as notification (notification.id)}
              <li class="flex items-center p-3 bg-white bg-opacity-20 rounded-lg">
                <div class="mr-4">
                  {#if notification.type === 'add'}
                    <span class="text-green-400 text-2xl">+</span>
                  {:else if notification.type === 'edit'}
                    <span class="text-yellow-400 text-2xl">✎</span>
                  {:else if notification.type === 'delete'}
                    <span class="text-red-400 text-2xl">-</span>
                  {/if}
                </div>
                <div class="flex-grow">
                  <p>{notification.message}</p>
                  <p class="text-sm text-gray-300">{formatNotificationDate(notification.created_at)}</p>
                </div>
              </li>
            {/each}
            {#each upcomingEvents.sort((a, b) => a.date.getTime() - b.date.getTime()) as event (event.id)}
              <li class="flex items-center p-3 bg-white bg-opacity-20 rounded-lg">
                <div class="mr-4">
                  <span class="text-blue-400 text-2xl">!</span>
                </div>
                <div class="flex-grow">
                  <p>Upcoming: {event.title}</p>
                  <p class="text-sm text-gray-300">{formatDate(event.date)}</p>
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="text-gray-200">No notifications or upcoming events.</p>
        {/if}
      </div>
    {/if}

    {#if showNotification}
      <div
        in:fly={{ y: -50, duration: 300, easing: cubicOut }}
        out:fade
        class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg"
      >
        <p class="text-lg font-semibold">{notificationMessage}</p>
      </div>
    {/if}
  </div>

  {#if showModal}
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div in:fade={{ duration: 300 }} class="bg-white bg-opacity-90 backdrop-blur-md p-6 rounded-lg w-96">
        <h2 class="text-2xl font-semibold mb-4 text-gray-800">
          {editingEvent ? 'Edit Event' : 'Add New Event'}
        </h2>
        <input
          type="text"
          bind:value={newEventTitle}
          placeholder="Event title"
          class="w-full p-2 border rounded mb-4 bg-white text-black bg-opacity-50"
        />
        <div class="flex justify-end">
          <button
            on:click={() => showModal = false}
            class="px-4 py-2 text-gray-600 hover:text-gray-800 mr-2"
          >
            Cancel
          </button>
          <button
            on:click={editingEvent ? updateEvent : addEvent}
            class="px-6 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full text-white font-semibold hover:from-yellow-500 hover:to-orange-600 transition-all duration-300"
          >
            {editingEvent ? 'Update' : 'Add'}
          </button>
        </div>
      </div>
    </div>
  {/if}
</main>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');

  :global(body) {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
</style>

