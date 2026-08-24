document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("serviceSearch");
    const categorySelect = document.getElementById("serviceCategory");
    const noResults = document.getElementById("noResults");

    // Get all service cards
    const serviceCards = document.querySelectorAll(".service-card");


    function filterServices() {

        const searchValue = searchInput.value
            .toLowerCase()
            .trim();

        const selectedCategory = categorySelect.value;

        let visibleCount = 0;


        serviceCards.forEach(function (card) {

            const serviceName = (
                card.getAttribute("data-name") || ""
            ).toLowerCase();

            const serviceCategory = (
                card.getAttribute("data-category") || ""
            ).toLowerCase();


            // Search condition
            const matchesSearch =
                serviceName.includes(searchValue);


            // Category condition
            const matchesCategory =
                selectedCategory === "all" ||
                serviceCategory === selectedCategory;


            // Show / hide card
            if (matchesSearch && matchesCategory) {

                card.style.display = "";

                visibleCount++;

            } else {

                card.style.display = "none";

            }

        });


        // Show "No services found"
        if (visibleCount === 0) {

            noResults.style.display = "block";

        } else {

            noResults.style.display = "none";

        }

    }


    // Search while typing
    searchInput.addEventListener(
        "input",
        filterServices
    );


    // Filter when category changes
    categorySelect.addEventListener(
        "change",
        filterServices
    );


});