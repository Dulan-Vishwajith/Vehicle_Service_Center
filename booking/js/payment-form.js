document.addEventListener(
    'DOMContentLoaded',
    function () {

        const buttons =
            document.querySelectorAll(
                '.payment-method-button'
            );

        const cardPanel =
            document.getElementById(
                'cardPaymentPanel'
            );

        const bankPanel =
            document.getElementById(
                'bankPaymentPanel'
            );


        buttons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        buttons.forEach(
                            function (item) {

                                item.classList.remove(
                                    'active'
                                );

                            }
                        );


                        button.classList.add(
                            'active'
                        );


                        const method =
                            button.dataset.paymentMethod;


                        if (method === 'card') {

                            cardPanel.style.display =
                                'block';

                            bankPanel.style.display =
                                'none';

                        } else {

                            cardPanel.style.display =
                                'none';

                            bankPanel.style.display =
                                'block';
                        }

                    }
                );

            }
        );


        /*
        | Card number formatting
        */

        const cardNumber =
            document.querySelector(
                'input[name="card_number"]'
            );


        if (cardNumber) {

            cardNumber.addEventListener(
                'input',
                function () {

                    let value =
                        this.value.replace(
                            /\D/g,
                            ''
                        ).substring(
                            0,
                            16
                        );


                    this.value =
                        value.replace(
                            /(.{4})/g,
                            '$1 '
                        ).trim();

                }
            );

        }


        /*
        | Expiry formatting
        */

        const expiry =
            document.querySelector(
                'input[name="expiry"]'
            );


        if (expiry) {

            expiry.addEventListener(
                'input',
                function () {

                    let value =
                        this.value.replace(
                            /\D/g,
                            ''
                        ).substring(
                            0,
                            4
                        );


                    if (value.length > 2) {

                        value =
                            value.substring(0, 2)
                            + '/'
                            + value.substring(2);

                    }

                    this.value = value;

                }
            );

        }


        /*
        | Double-submit protection
        */

        document
            .querySelectorAll('form')
            .forEach(
                function (form) {

                    form.addEventListener(
                        'submit',
                        function () {

                            const button =
                                form.querySelector(
                                    'button[type="submit"]'
                                );

                            if (button) {

                                button.disabled =
                                    true;

                                button.textContent =
                                    'Processing...';

                            }

                        }
                    );

                }
            );

    }
);
