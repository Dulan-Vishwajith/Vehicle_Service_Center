
document.addEventListener("DOMContentLoaded", function () {

    const profileDropdown = document.querySelector(".profile-dropdown");
    const profileButton = document.querySelector(".profile-dropdown-toggle");

    if (!profileDropdown || !profileButton) {
        return;
    }

    profileButton.addEventListener("click", function (event) {

        event.stopPropagation();

        profileDropdown.classList.toggle("active");

    });


    // Close when clicking outside

    document.addEventListener("click", function (event) {

        if (!profileDropdown.contains(event.target)) {

            profileDropdown.classList.remove("active");

        }

    });

});
