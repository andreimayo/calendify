<script>
    import { onMount } from 'svelte';
    import { fade, fly } from 'svelte/transition';
    import { cubicOut } from 'svelte/easing';
  
    let appName = "Calendify";
    let visible = false;
  
    onMount(() => {
      console.log(`Welcome to ${appName}`);
      visible = true;
    });
  
    const navigateToCalendar = () => {
      window.location.href = "/calendar";
    };
  </script>
  
  <main class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-emerald-400 via-cyan-500 to-blue-500 text-white">
    {#if visible}
      <div 
        class="text-center p-12 rounded-2xl shadow-2xl bg-white bg-opacity-10 backdrop-blur-md"
        in:fade={{ duration: 1000, delay: 300 }}
      >
        <h1 
          class="text-7xl font-extrabold mb-6 text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-yellow-500"
          in:fly={{ y: -50, duration: 1000, delay: 500, easing: cubicOut }}
        >
          Welcome to {appName}
        </h1>
        <p 
          class="text-2xl mb-8 font-light text-cyan-100"
          in:fly={{ y: 50, duration: 1000, delay: 700, easing: cubicOut }}
        >
          Your ultimate tool for managing and scheduling events effortlessly.
        </p>
  
        <button
          class="px-10 py-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xl font-bold rounded-full shadow-lg hover:from-yellow-500 hover:to-orange-600 hover:shadow-xl transition-all duration-300 transform hover:scale-105"
          on:click={navigateToCalendar}
          in:fade={{ duration: 1000, delay: 1000 }}
        >
          Get Started
        </button>
      </div>
    {/if}
  </main>
  
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
  
    :global(body) {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
  
    main {
      font-family: 'Poppins', sans-serif;
    }
  
    h1 {
      letter-spacing: -2px;
      line-height: 1.2;
    }
  
    button {
      outline: none;
      border: none;
    }
  </style>
  
  