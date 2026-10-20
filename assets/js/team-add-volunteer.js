/**
 * Volunteer Team Addition Form Handler
 *
 * Handles the submission of the volunteer team addition form using AJAX to avoid page reload.
 */

(function() {
    'use strict';
    //transform data from button click to form submission

    const dataButton = document.querySelectorAll('.assign-to-team-button');
    if (!dataButton) return; // Exit if button doesn't exist on this page

    dataButton.forEach(button => {
        button.addEventListener('click', function() {
            const conference = button.getAttribute('data-conference');
            const volunteer = button.getAttribute('data-volunteer');

            //fecth available teams for the conference and populate a select element in a dialog box
            fetch(`/backbone/get-teams?c=${conference}&v=${volunteer}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const teams = data.teams;
                        //create a select element with the teams
                        const select = document.createElement('select');
                        select.id = 'team-select';
                        select.name = 'team';
                        teams.forEach(team => {
                            const option = document.createElement('option');
                            option.value = team.slug;
                            option.textContent = team.name;
                            select.appendChild(option);
                        });
                        console.log(`Available teams for conference ${conference}:`, teams);
                        //create a dialog box with the select element and a submit button
                        const dialog = document.createElement('dialog');
                        dialog.classList.add('dialog');
                        dialog.innerHTML = `
                    <h3>Add to team</h3>
                    <form id="add-volunteer-form">
                        <label for="team-select">Team:</label>
                    </form>
                `;
                        dialog.querySelector('#add-volunteer-form').appendChild(select);
                        const submitButton = document.createElement('button');
                        submitButton.type = 'submit';
                        submitButton.classList.add('btn');
                        submitButton.textContent = 'Add';
                        dialog.querySelector('#add-volunteer-form').appendChild(submitButton);
                        document.body.appendChild(dialog);
                        dialog.showModal();
                        //handle form submission
                        dialog.querySelector('#add-volunteer-form').addEventListener('submit', function (event) {
                            event.preventDefault();
                            //get the selected team
                            const teamSelect = dialog.querySelector('#team-select');
                            const team = teamSelect.value;
                            if (!team) {
                                alert('Please select a team.');
                                return;
                            }
                            //send the data to the server
                            fetch('/backbone/team/add-volunteer', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    c: conference,
                                    t: team,
                                    volunteer: volunteer
                                })
                            })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    console.log(data);
                                    if (data.success) {
                                        //reload the page to reflect the changes
                                        window.location.reload();
                                    } else {
                                        throw new Error(data.message || 'Unknown error occurred');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error adding volunteer to the team. Please try again.');
                                });
                        });
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading teams. Please try again.');
                });
        });
    });
})();