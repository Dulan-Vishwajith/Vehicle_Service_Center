document.addEventListener("DOMContentLoaded", function () {

    const navLinks = document.querySelectorAll(
        ".main-nav a[data-section]"
    );

    const sections = document.querySelectorAll(
        "#home, #services, #packages, #offers, #contact"
    );


    /* =========================================================
       SET ACTIVE NAVIGATION
       ========================================================= */

    function setActiveSection(sectionId) {

        navLinks.forEach(function (link) {

            link.classList.remove("active");

            if (link.dataset.section === sectionId) {
                link.classList.add("active");
            }

        });

    }


    /* =========================================================
       CLICK NAVIGATION
       ========================================================= */

    navLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            const sectionId = this.dataset.section;

            setActiveSection(sectionId);

        });

    });


    /* =========================================================
       DETECT CURRENT SECTION WHILE SCROLLING
       ========================================================= */

    if (sections.length > 0) {

        const observer = new IntersectionObserver(
            function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        setActiveSection(entry.target.id);

                    }

                });

            },
            {
                root: null,

                /*
                 * Header is fixed, so start detecting
                 * the section slightly below it.
                 */
                rootMargin: "-90px 0px -55% 0px",

                threshold: 0
            }
        );


        sections.forEach(function (section) {

            observer.observe(section);

        });

    }


    /* =========================================================
       HANDLE HASH WHEN PAGE LOADS
       ========================================================= */

    const currentHash = window.location.hash.replace("#", "");

    if (
        currentHash === "home" ||
        currentHash === "services" ||
        currentHash === "packages" ||
        currentHash === "offers" ||
        currentHash === "contact"
    ) {

        setActiveSection(currentHash);

    } else {

        // No section on the current page.
        // This prevents Home from being highlighted
        // on Dashboard and other pages.

        navLinks.forEach(function (link) {
            link.classList.remove("active");
        });

    }

});