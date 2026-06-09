<input type="hidden" name="time_spent" id="time_spent_input" value="0">

<script>
let startTime = Date.now();

// This runs every second to calculate total minutes spent
setInterval(function() {
    let currentTime = Date.now();
    let secondsSpent = Math.floor((currentTime - startTime) / 1000);
    
    // We use Math.max(1, ...) so even a fast exam counts as 1 minute
    let minutesSpent = Math.max(1, Math.ceil(secondsSpent / 60)); 
    
    document.getElementById('time_spent_input').value = minutesSpent;
}, 1000);
</script>