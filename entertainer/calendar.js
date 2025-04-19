const calendarGrid = document.getElementById('calendarGrid');
const monthYear = document.getElementById('monthYear');
const prevMonth = document.getElementById('prevMonth');
const nextMonth = document.getElementById('nextMonth');

let currentDate = new Date();
const today = new Date(); // Today's date to compare with

async function fetchSchedule(month, year) {
    try {
        const response = await fetch(`getSchedule.php?month=${month}&year=${year}`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching schedule:', error);
    }
}

async function generateCalendar(date) {
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const schedule = await fetchSchedule(month, year);

    calendarGrid.innerHTML = '';
    const firstDay = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    monthYear.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

    // Create empty slots for days of the week before the 1st of the month
    for (let i = 0; i < firstDay; i++) {
        const emptyDiv = document.createElement('div');
        calendarGrid.appendChild(emptyDiv);
    }

    // Loop through all days in the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('date-box');

        const dayNumber = document.createElement('div');
        dayNumber.classList.add('day-number');
        dayNumber.textContent = day;
        dayDiv.appendChild(dayNumber);

        // Format the current day to match database format (YYYY-MM-DD)
        const currentDay = new Date(year, month - 1, day);
        const formattedDate = currentDay.toISOString().split('T')[0];

        // Find matching schedule entry using formatted date string
        const scheduleEntry = schedule.find(entry => entry.date === formattedDate);

        // Only add button for scheduled dates that aren't in the past
        if (scheduleEntry && currentDay >= today) {
            const bookButton = document.createElement('button');
            bookButton.classList.add('book-now');

            // Handle based on current_status from the database
            switch (scheduleEntry.current_status) {
                case 'Booked':
                    bookButton.textContent = 'Booked';
                    bookButton.disabled = true;
                    dayDiv.classList.add('booked');
                    dayDiv.appendChild(bookButton);
                    break;
                case 'Pending':
                    bookButton.textContent = 'Pending';
                    bookButton.disabled = true;
                    dayDiv.classList.add('pending');
                    dayDiv.appendChild(bookButton);
                    break;
                case 'Available':
                    bookButton.textContent = 'Book now';
                    dayDiv.classList.add('available');
                    dayDiv.appendChild(bookButton);
                    break;
            }
        } else {
            dayDiv.classList.add('disabled');
        }

        calendarGrid.appendChild(dayDiv);
    }
}


prevMonth.addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar(currentDate);
});

nextMonth.addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar(currentDate);
});

generateCalendar(currentDate);


document.getElementById('scheduleButton').addEventListener('click', async () => {
    const button = document.getElementById('scheduleButton');
    button.disabled = true;
    button.textContent = 'Submitting...';

    // Get values from input fields
    const startDate = document.getElementById('start_date').value; // Changed from 'date'
    const endDate = document.getElementById('end_date').value; // New input for end date
    const startTime = document.getElementById('start_time').value; // Start time
    const endTime = document.getElementById('end_time').value; // End time

    // Validate all required fields
    if (!startDate || !endDate || !startTime || !endTime ) {
        alert('Please fill out all fields.');
        button.disabled = false;
        button.textContent = 'Schedule';
        return;
    }

    // Further validation can be added here if needed (e.g., date formats)

    try {
        const response = await fetch('scheduleAppointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                start_date: startDate, // Sending start date
                end_date: endDate, // Sending end date
                start_time: startTime, // Sending start time
                end_time: endTime, // Sending end time
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Appointment scheduled successfully!');
            generateCalendar(currentDate); // Refresh calendar if needed
        } else {
            alert(`Failed to schedule appointment: ${result.message}`);
        }
    } catch (error) {
        console.error('Error scheduling appointment:', error);
        alert('An error occurred while scheduling the appointment.');
    } finally {
        button.disabled = false;
        button.textContent = 'Schedule'; // Reset button text
    }
});

