<script lang="ts">
    import { onMount } from 'svelte';
    import { fade, fly } from 'svelte/transition';
    import { cubicOut } from 'svelte/easing';
  
    interface Event {
      id: number;
      title: string;
      date: Date;
    }
  
    let currentDate: Date = new Date();
    let selectedDate: Date = new Date();
    let events: Event[] = [];
    let newEventTitle: string = '';
    let editingEvent: Event | null = null;
    let showModal: boolean = false;
    let showEventList: boolean = false;
  
    // Safe deserialization from localStorage
    onMount(() => {
      const storedEvents = localStorage.getItem('calendarEvents');
      if (storedEvents) {
        events = JSON.parse(storedEvents).map((event: any): Event => ({
          id: event.id ?? Date.now(), // Ensure id is always a number
          title: event.title || 'Untitled Event',
          date: event.date ? new Date(event.date) : new Date() // Ensure valid Date
        }));
      }
    });
  
    // Save to localStorage when events change
    $: {
      if (events.length > 0) {
        localStorage.setItem('calendarEvents', JSON.stringify(events));
      }
    }
  
    // Utility Functions
    function getDaysInMonth(year: number, month: number): number {
      return new Date(year, month + 1, 0).getDate();
    }
  
    function getFirstDayOfMonth(year: number, month: number): number {
      return new Date(year, month, 1).getDay();
    }
  
    // Reactive statements for calendar grid
    $: daysInMonth = getDaysInMonth(currentDate.getFullYear(), currentDate.getMonth());
    $: firstDayOfMonth = getFirstDayOfMonth(currentDate.getFullYear(), currentDate.getMonth());
  
    // Navigation
    function previousMonth(): void {
      currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
    }
  
    function nextMonth(): void {
      currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
    }
  
    function selectDate(day: number): void {
      selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    }
  
    // Event Management
    function addEvent(): void {
      if (newEventTitle.trim()) {
        const newEvent: Event = {
          id: Date.now(),
          title: newEventTitle,
          date: new Date(selectedDate) // Ensure it's a Date object
        };
        events = [...events, newEvent];
        newEventTitle = '';
        showModal = false;
      }
    }
  
    function editEvent(event: Event): void {
      editingEvent = {
        id: event.id,
        title: event.title,
        date: new Date(event.date)
      };
      newEventTitle = event.title;
      showModal = true;
    }
  
    function updateEvent(): void {
      if (editingEvent && newEventTitle.trim()) {
        events = events.map(e => 
          e.id === editingEvent?.id ? { ...editingEvent, title: newEventTitle } : e
        );
        newEventTitle = '';
        editingEvent = null;
        showModal = false;
      }
    }
  
    function deleteEvent(id: number): void {
      events = events.filter(e => e.id !== id);
    }
  
    // Filter events for selected date
    $: selectedDateEvents = events.filter(
      (event: Event) => event.date?.toDateString() === selectedDate.toDateString()
    );
  
    function formatDate(date: Date): string {
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }
  </script>
  
  <main class="min-h-screen bg-gradient-to-br from-emerald-400 via-cyan-500 to-blue-500 text-white p-4">
    <div class="container mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-yellow-500">
          Calendify
        </h1>
        <div class="space-x-4">
          <button
            on:click={() => showEventList = !showEventList}
            class="px-6 py-2 bg-white bg-opacity-20 rounded-full text-white font-semibold hover:bg-opacity-30 transition-all duration-300"
          >
            {showEventList ? 'Hide' : 'Show'} Event List
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
        <!-- Calendar Section -->
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
            {#each Array(daysInMonth).fill().map((_, i) => i + 1) as day}
              <button
                class="p-2 text-center rounded hover:bg-white hover:bg-opacity-20 transition-colors"
                class:bg-yellow-300={selectedDate.getDate() === day}
                on:click={() => selectDate(day)}
              >
                {day}
              </button>
            {/each}
          </div>
        </div>
  
        <!-- Events Section -->
        <div class="flex-1 bg-white bg-opacity-10 backdrop-blur-md rounded-2xl shadow-xl p-6">
          <h3 class="text-2xl font-semibold mb-4">Events for {formatDate(selectedDate)}</h3>
          {#if selectedDateEvents.length > 0}
            <ul class="space-y-2">
              {#each selectedDateEvents as event (event.id)}
                <li class="flex justify-between items-center p-3 bg-white bg-opacity-20 rounded-lg">
                  <span>{event.title}</span>
                  <div>
                    <button on:click={() => editEvent(event)} class="text-yellow-200 hover:text-yellow-400 mr-2">Edit</button>
                    <button on:click={() => deleteEvent(event.id)} class="text-red-300 hover:text-red-500">Delete</button>
                  </div>
                </li>
              {/each}
            </ul>
          {:else}
            <p class="text-gray-200">No events for this date.</p>
          {/if}
          <button on:click={() => { editingEvent = null; showModal = true; }} class="mt-4 px-6 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full text-white">
            Add Event
          </button>
        </div>
      </div>
    </div>
  
    <!-- Modal -->
    {#if showModal}
      <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg">
          <h2 class="text-2xl font-semibold mb-4">{editingEvent ? 'Edit Event' : 'Add New Event'}</h2>
          <input bind:value={newEventTitle} placeholder="Event title" class="w-full p-2 border rounded mb-4" />
          <div class="flex justify-end">
            <button on:click={() => showModal = false} class="text-gray-600 mr-2">Cancel</button>
            <button on:click={editingEvent ? updateEvent : addEvent} class="bg-yellow-400 px-6 py-2 rounded text-white">
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
    }
  </style>
  