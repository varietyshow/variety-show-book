//
i was in the process of creating website for entertainer booking system. i want you to help in creating this system. 
i have the php files namely calendar.js, config.php, db_connect.php, entertainer-dashboardpage.php, entertainer-loginpage.php, getSchedule.php, scheduleAppointment.php. you will ask the code of each files i give you and i will give the code on each files. after i am done giving the code, you will ask me what help i need.



//

.container {
    display: flex;
    flex-direction: row;
    padding: 100px;
    margin-top: -5%;
}

.left-panel {
    width: 70%; /* Increased width */
    box-sizing: border-box;
    background-color: white;
    margin-right: 20px; /* Space between panels */
}

.right-panel {
    width: 30%; /* Reduced width */
    box-sizing: border-box;
    background-color: white;
}

.right-panel {
    margin-left: 20px; /* Optional: for consistency */
}



.left-panel .calendar {
    display: block;
}

.calendar-header {
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 15px;
}

.calendar-header button {
    width: 100%;
    margin-top: 10px;
}

.calendar-header h2 {
    font-size: 20px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* Seven columns for days of the week */
    gap: 5px; /* Space between days */
    margin-top: 10px;
}

.date-box {
    display: flex;
    flex-direction: column;
    align-items: center; /* Center the content horizontally */
    justify-content: center; /* Center the content vertically */
    height: 80px; /* Adjust height as needed */
    border: 1px solid #ddd; /* Optional: border for better visibility */
    background-color: #f9f9f9; /* Optional: background color for the date boxes */
}

.day-number {
    font-size: 18px; /* Larger font size for day numbers */
    margin-bottom: 5px; /* Space between day number and booking button */
}

.book-now {
    font-size: 12px;
    padding: 4px;
}


.legend {
    margin-top: 15px;
}

.legend p {
    margin: 3px 0;
}

.right-panel h2 {
    font-size: 20px;
}

.form-group input[type="text"], .form-group input[type="date"], .form-group button {
    font-size: 14px;
}

.form-group button {
    padding: 8px;
}

/* Media Query for Mobile Devices */
@media (max-width: 600px) {
    .container {
        flex-direction: row;
    }

    .left-panel, .right-panel {
        width: 100%;
        margin: 0;
    }
    
    .left-panel {
        margin-bottom: 20px;
    }
    
    .right-panel {
        margin-bottom: 0;
    }
    
    .calendar-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}




// Function to fetch available entertainers based on date and time input
  document.getElementById('appointment_date').addEventListener('change', fetchAvailableEntertainers);
document.getElementById('start_time').addEventListener('change', fetchAvailableEntertainers);
document.getElementById('end_time').addEventListener('change', fetchAvailableEntertainers);

// Function to fetch available entertainers based on date and time input
function fetchAvailableEntertainers() {
    const selectedDate = document.getElementById('appointment_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;

    if (selectedDate && startTime && endTime) {
        fetch(`fetch_entertainers.php?date=${selectedDate}&start_time=${startTime}&end_time=${endTime}`)
            .then(response => response.json())
            .then(data => {
                const entertainerGrid = document.querySelector('.entertainer-grid');
                entertainerGrid.innerHTML = '';

                if (data.length > 0) {
                    data.forEach(entertainer => {
                        const roles = entertainer.roles ? entertainer.roles.split(',') : []; // Split roles if available
                        
                        const card = document.createElement('div');
                        card.classList.add('entertainer-card');
                        card.setAttribute('data-entertainer-id', entertainer.entertainer_id);
                        
                        card.innerHTML = `
                            <div class="entertainer-image" style="background-image: url('../images/${entertainer.profile_image}'); height: 200px; position: relative;"></div>
                            <h3>${entertainer.title}</h3>
                            <div class="role-selection">
                                <label>Select Role:</label>
                                <div class="roles-list">
                                    ${roles.map(role => `
                                        <div class='role-container'>
                                            <input type='checkbox' name='roles[]' value='${role.trim()}'>
                                            <label>${role.trim()}</label>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;

                        entertainerGrid.appendChild(card);
                    });

                    // Call the setup function to bind events to new cards
                    setupEntertainerCardSelection();

                    // Call updateSelectedEntertainers to ensure it reflects the new entertainers
                    updateSelectedEntertainers();
                } else {
                    entertainerGrid.innerHTML = '<p>No entertainers available at this time.</p>';
                }
            })
            .catch(error => console.error('Error fetching entertainers:', error));
    }
}

function setupEntertainerCardSelection() {
    document.querySelectorAll('.entertainer-card').forEach(card => {
        // Event listener for entertainer card click
        card.addEventListener('click', function () {
            const isSelected = this.classList.toggle('selected'); // Toggle selected class

            // Update all checkboxes within that entertainer card
            const checkboxes = this.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => checkbox.checked = isSelected); // Sync checkbox with card selection

            updateSelectedEntertainers(); // Update selected entertainers
        });

        // Find checkboxes within the entertainer card and add event listener
        card.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('click', function (event) {
                // Prevent event from bubbling up to the card click
                event.stopPropagation();

                // Check if the checkbox is checked
                if (this.checked) {
                    card.classList.add('selected'); // If checked, add the selected class
                } else {
                    // If unchecked, check if any other checkboxes are still checked
                    const currentChecked = card.querySelectorAll('input[type="checkbox"]:checked');

                    // Only remove selected class if no checkboxes are checked
                    if (currentChecked.length === 0) {
                        card.classList.remove('selected'); // Remove the selected class if all are unchecked
                    }
                }

                updateSelectedEntertainers(); // Update selected entertainers and roles
            });
        });
    });
}