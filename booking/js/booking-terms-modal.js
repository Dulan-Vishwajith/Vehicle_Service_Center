document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("termsModal");

    const openButton =
        document.getElementById("openTermsModal");

    const closeButton =
        document.getElementById("closeTermsModal");

    const cancelButton =
        document.getElementById("termsModalCancel");

    const agreeButton =
        document.getElementById("termsModalAgree");

    const overlay =
        document.getElementById("termsModalOverlay");

    const checkbox =
        document.getElementById("termsCheckbox");


    if (!modal) {
        return;
    }


    /* =====================================================
       OPEN MODAL
       ===================================================== */

    function openModal() {

        modal.classList.add("active");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.style.overflow = "hidden";

    }


    /* =====================================================
       CLOSE MODAL
       ===================================================== */

    function closeModal() {

        modal.classList.remove("active");

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.style.overflow = "";

    }


    /* =====================================================
       OPEN
       ===================================================== */

    if (openButton) {

        openButton.addEventListener(
            "click",
            openModal
        );

    }


    /* =====================================================
       CLOSE BUTTON
       ===================================================== */

    if (closeButton) {

        closeButton.addEventListener(
            "click",
            closeModal
        );

    }


    /* =====================================================
       CANCEL
       ===================================================== */

    if (cancelButton) {

        cancelButton.addEventListener(
            "click",
            closeModal
        );

    }


    /* =====================================================
       CLICK OUTSIDE
       ===================================================== */

    if (overlay) {

        overlay.addEventListener(
            "click",
            closeModal
        );

    }


    /* =====================================================
       I AGREE
       ===================================================== */

    if (agreeButton) {

        agreeButton.addEventListener(
            "click",
            function () {

                if (checkbox) {

                    checkbox.checked = true;

                    checkbox.dispatchEvent(
                        new Event(
                            "change",
                            {
                                bubbles: true
                            }
                        )
                    );

                }

                closeModal();

            }
        );

    }


    /* =====================================================
       ESC KEY
       ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal.classList.contains("active")
            ) {

                closeModal();

            }

        }
    );

});