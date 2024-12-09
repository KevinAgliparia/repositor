// Open modal
function openModal() {
    document.getElementById('upload-modal').style.display = 'block';
}

// Close modal
function closeModal() {
    document.getElementById('upload-modal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('upload-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

// Close Success Modal
function closeSuccessModal() {
    document.getElementById("success-modal").style.display = "none";
}

// Close Delete Modal
function closeDeleteModal() {
    document.getElementById("delete-modal").style.display = "none";
}

// Search Projects
function searchProjects() {
    const searchValue = document.getElementById('search-bar').value.toLowerCase();
    const projectCards = document.querySelectorAll('.project-card');

    projectCards.forEach((card) => {
        const projectTitle = card.querySelector('.project-title').textContent.toLowerCase();
        if (projectTitle.includes(searchValue)) {
            card.style.display = 'block'; // Show matching projects
        } else {
            card.style.display = 'none'; // Hide non-matching projects
        }
    });
}

function openProjectModal(projectId) {
    const modal = document.getElementById(`project-modal-${projectId}`);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeProjectModal(projectId) {
    const modal = document.getElementById(`project-modal-${projectId}`);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside it
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};

// Function to show the edit form
function showEditForm(projectId) {
    document.getElementById('view-section-' + projectId).style.display = 'none';
    document.getElementById('edit-section-' + projectId).style.display = 'block';
}

// Function to hide the edit form
function hideEditForm(projectId) {
    document.getElementById('view-section-' + projectId).style.display = 'block';
    document.getElementById('edit-section-' + projectId).style.display = 'none';
}
function confirmDelete() {
    return confirm("Are you sure you want to delete this project? This action cannot be undone.");
}
function submitForm(projectId) {
    const form = document.getElementById("myForm");
    const projectIdField = document.createElement("input");
    projectIdField.type = "hidden";
    projectIdField.name = "project_id";
    projectIdField.value = projectId;

    form.appendChild(projectIdField);
    form.submit();
}

function confirmDownload(filePath) {
    const fileName = filePath.split('/').pop(); // Extract the file name from the file path
    return confirm(`Are you sure you want to download "${fileName}"?`);
}

// Add a new member input field
document.addEventListener('click', function (event) {
    // Ensure the correct button triggers the action
    if (event.target.id === 'add-member-btn' || event.target.classList.contains('add-member-btn')) {
        const containerId = event.target.getAttribute('data-container-id');
        const container = document.getElementById(containerId) || document.getElementById('members-container');

        if (container) {
            const newMemberDiv = document.createElement('div');
            newMemberDiv.classList.add('member-input');

            // Create the input field
            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = 'project_members[]';
            newInput.placeholder = 'Enter member name';
            newInput.required = true;

            // Create the remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = function () {
                removeMember(removeBtn);
            };

            // Append input and button to the container
            newMemberDiv.appendChild(newInput);
            newMemberDiv.appendChild(removeBtn);

            container.appendChild(newMemberDiv);
        }
    }
});

// Remove a member input field
function removeMember(button) {
    const memberDiv = button.parentElement;
    memberDiv.remove();
}

// Combine member inputs into a single comma-separated string on form submission
document.addEventListener('submit', function (event) {
    const form = event.target;
    const memberInputs = form.querySelectorAll('input[name="project_members[]"]');
    const members = Array.from(memberInputs).map(input => input.value.trim());
    const membersInput = form.querySelector('input[name="project_members_combined"]');

    if (!membersInput) {
        // If hidden input doesn't exist, create it
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'project_members_combined';
        hiddenInput.value = members.join(',');
        form.appendChild(hiddenInput);
    } else {
        // Update the existing hidden input value
        membersInput.value = members.join(',');
    }
});
