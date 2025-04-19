<?php
// modal.php
?>
<!-- Availability Modal -->
<div id="availabilityModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Entertainer Availability</h2>
        <div id="modalContent"></div>
    </div>
</div>

<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow: auto;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 8px;
        position: relative;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: absolute;
        right: 10px;
        top: 5px;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .availability-message {
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .available {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .unavailable {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<script>
function initializeModal() {
    const modal = document.getElementById('availabilityModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.querySelector('.close');
    const checkButton = document.getElementById('checkAvailability');

    if (!modal || !modalContent || !closeBtn || !checkButton) {
        console.error('Required modal elements not found');
        return;
    }

    // Close modal when clicking X
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Function to check availability
    async function checkAvailability() {
        const selectedEntertainers = document.querySelectorAll('input[name="entertainers[]"]:checked');
        const date = document.getElementById('date');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');

        // Validate all fields are filled
        if (!date?.value || !startTime?.value || !endTime?.value || selectedEntertainers.length === 0) {
            alert('Please fill in all required fields:\n- Select at least one entertainer\n- Select a date\n- Select start and end times');
            return;
        }

        modalContent.innerHTML = ''; // Clear previous results
        modal.style.display = "block";

        // Check each selected entertainer
        for (const entertainer of selectedEntertainers) {
            const formData = new FormData();
            formData.append('entertainer_id', entertainer.value);
            formData.append('booking_date', date.value);
            formData.append('start_time', startTime.value);
            formData.append('end_time', endTime.value);

            try {
                const response = await fetch('check_availability.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                const messageDiv = document.createElement('div');
                messageDiv.className = `availability-message ${data.available ? 'available' : 'unavailable'}`;
                messageDiv.textContent = data.message;
                modalContent.appendChild(messageDiv);
            } catch (error) {
                console.error('Error:', error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'availability-message unavailable';
                errorDiv.textContent = 'Error checking availability. Please try again.';
                modalContent.appendChild(errorDiv);
            }
        }
    }

    // Add click event to the Check Availability button
    checkButton.addEventListener('click', checkAvailability);
}

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeModal);
} else {
    initializeModal();
}
</script>
