function showDetails(projectName, username, uploadDate, overview, photoPath, url) {
    document.getElementById('modal-project-name').textContent = projectName || 'No Name Provided';
    const photo = document.getElementById('modal-photo');
    if (photoPath) {
        photo.src = photoPath;
        photo.style.display = 'block';
    } else {
        photo.style.display = 'none';
    }
    document.getElementById('modal-username').textContent = username || 'Unknown User';
    document.getElementById('modal-upload-date').textContent = uploadDate || 'Unknown Date';
    document.getElementById('modal-overview').textContent = overview || 'No Overview Available';
    const modalUrl = document.getElementById('modal-url');
    if (url) {
        modalUrl.href = url;
        modalUrl.style.display = 'block';
    } else {
        modalUrl.style.display = 'none';
    }
    document.getElementById('project-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('project-modal').style.display = 'none';
}

function filterProjects() {
    const searchValue = document.getElementById("search").value.toLowerCase();
    const projects = document.getElementsByClassName("project-card");

    for (let i = 0; i < projects.length; i++) {
        const projectName = projects[i].getElementsByTagName("h4")[0].textContent.toLowerCase();
        const uploadedBy = projects[i].getElementsByTagName("p")[0].textContent.toLowerCase();

        if (projectName.includes(searchValue) || uploadedBy.includes(searchValue)) {
            projects[i].style.display = "block"; // Show matching cards
        } else {
            projects[i].style.display = "none"; // Hide non-matching cards
        }
    }
}

