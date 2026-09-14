<?php

/*
|--------------------------------------------------------------------------
| MANAGEMENT - HERO IMAGE
|--------------------------------------------------------------------------
| Allows management employees to change the website hero background image.
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
| Role 3 = Management Employee
|--------------------------------------------------------------------------
*/

if ((int) ($_SESSION['role_id'] ?? 0) !== 3) {

    header("Location: ../../login/login-form.php?error=invalid_role");
    exit();
}


/*
|--------------------------------------------------------------------------
| IMAGE PATH
|--------------------------------------------------------------------------
|
| Current file:
| dashboard/managementEmployee/functions/manage-hero-image.php
|
| Target:
| public/images/hero-car.jpg
|
*/

$imagesDirectory = __DIR__ . '/../../../public/images';

$heroImage = $imagesDirectory . '/hero-car.jpg';


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['hero_image_csrf'])) {

    $_SESSION['hero_image_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION['hero_image_csrf'];


/*
|--------------------------------------------------------------------------
| HANDLE IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['hero_image_csrf'],
            $_POST['csrf_token']
        )
    ) {

        $message =
            'Invalid security request. Please try again.';

        $messageType =
            'error';

    }

    /*
    |--------------------------------------------------------------------------
    | CHECK FILE
    |--------------------------------------------------------------------------
    */

    elseif (
        !isset($_FILES['hero_image']) ||
        $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK
    ) {

        $message =
            'Please select an image to upload.';

        $messageType =
            'error';

    }

    else {

        $file =
            $_FILES['hero_image'];


        /*
        |--------------------------------------------------------------------------
        | MAXIMUM FILE SIZE
        |--------------------------------------------------------------------------
        | 5 MB
        |--------------------------------------------------------------------------
        */

        $maxFileSize =
            5 * 1024 * 1024;


        if ($file['size'] > $maxFileSize) {

            $message =
                'Image size must be less than 5 MB.';

            $messageType =
                'error';

        }

        else {

            /*
            |--------------------------------------------------------------------------
            | CHECK REAL IMAGE
            |--------------------------------------------------------------------------
            */

            $imageInfo =
                @getimagesize($file['tmp_name']);


            if ($imageInfo === false) {

                $message =
                    'The uploaded file is not a valid image.';

                $messageType =
                    'error';

            }

            else {

                $mimeType =
                    $imageInfo['mime'] ?? '';


                /*
                |--------------------------------------------------------------------------
                | JPG / JPEG ONLY
                |--------------------------------------------------------------------------
                */

                if ($mimeType !== 'image/jpeg') {

                    $message =
                        'Please upload a JPG or JPEG image only.';

                    $messageType =
                        'error';

                }

                else {

                    /*
                    |--------------------------------------------------------------------------
                    | MAKE SURE DIRECTORY EXISTS
                    |--------------------------------------------------------------------------
                    */

                    if (!is_dir($imagesDirectory)) {

                        if (!mkdir(
                            $imagesDirectory,
                            0755,
                            true
                        )) {

                            $message =
                                'Unable to create the images directory.';

                            $messageType =
                                'error';
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SAVE IMAGE
                    |--------------------------------------------------------------------------
                    */

                    if ($messageType !== 'error') {

                        /*
                        |--------------------------------------------------------------------------
                        | Upload directly to hero-car.jpg
                        |--------------------------------------------------------------------------
                        */

                        if (
                            move_uploaded_file(
                                $file['tmp_name'],
                                $heroImage
                            )
                        ) {

                            clearstatcache(
                                true,
                                $heroImage
                            );


                            $message =
                                'Hero background image updated successfully.';

                            $messageType =
                                'success';

                        }

                        else {

                            $message =
                                'Unable to upload the image.';

                            $messageType =
                                'error';
                        }
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CREATE PREVIEW
|--------------------------------------------------------------------------
|
| IMPORTANT:
| We use the actual PHP file here instead of a browser-relative URL.
|
| This completely avoids problems caused by dashboard routing.
|--------------------------------------------------------------------------
*/

$heroPreview = '';

if (
    file_exists($heroImage) &&
    is_readable($heroImage)
) {

    $imageData =
        @file_get_contents($heroImage);


    if ($imageData !== false) {

        $heroPreview =
            'data:image/jpeg;base64,' .
            base64_encode($imageData);
    }
}

?>


<!-- ========================================================
     PAGE HEADER
======================================================== -->

<div class="panel-header">

    <div>

        <h2>
            Manage Hero Image
        </h2>

        <p>
            Change the background image displayed in the website hero section.
        </p>

    </div>

</div>


<!-- ========================================================
     MESSAGE
======================================================== -->

<?php if ($message): ?>

    <div class="message <?= htmlspecialchars($messageType) ?>">

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<!-- ========================================================
     HERO IMAGE MANAGEMENT
======================================================== -->

<div class="hero-image-management">


    <!-- ====================================================
         CURRENT HERO IMAGE
    ==================================================== -->

    <div class="hero-image-preview">

        <h3>
            Current Hero Image
        </h3>


        <?php if ($heroPreview !== ''): ?>

            <img
                src="<?= $heroPreview ?>"
                alt="Current Hero Background"
                class="hero-preview-image"
            >

        <?php else: ?>

            <div class="hero-image-empty">

                <p>
                    No hero image has been uploaded yet.
                </p>

            </div>

        <?php endif; ?>

    </div>


    <!-- ====================================================
         CHANGE HERO IMAGE
    ==================================================== -->

    <div class="hero-image-upload">

        <h3>
            Change Hero Image
        </h3>


        <p class="hero-image-help">

            Upload a high-quality JPG image for the main
            website hero section.

        </p>


        <!-- ==================================================
             UPLOAD FORM
        ================================================== -->

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- ==================================================
                 CSRF TOKEN
            ================================================== -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >


            <!-- ==================================================
                 IMAGE FILE
            ================================================== -->

            <div class="hero-upload-field">

                <label for="hero_image">

                    Select New Hero Image

                </label>


                <input
                    type="file"
                    id="hero_image"
                    name="hero_image"
                    accept=".jpg,.jpeg,image/jpeg"
                    required
                >


                <small>

                    JPG/JPEG only · Maximum 5 MB

                </small>

            </div>


            <!-- ==================================================
                 BUTTON
            ================================================== -->

            <button
                type="submit"
                class="btn btn-primary"
            >

                Change Hero Image

            </button>


        </form>

    </div>

</div>


<!-- ========================================================
     OPTIONAL: PREVIEW SELECTED IMAGE BEFORE UPLOAD
======================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const input =
        document.getElementById('hero_image');

    const preview =
        document.querySelector('.hero-preview-image');


    if (!input || !preview) {
        return;
    }


    input.addEventListener('change', function () {

        const file =
            this.files[0];


        if (!file) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Check file type
        |--------------------------------------------------------------------------
        */

        if (file.type !== 'image/jpeg') {

            alert('Please select a JPG or JPEG image.');

            this.value = '';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Check file size
        |--------------------------------------------------------------------------
        */

        if (file.size > 5 * 1024 * 1024) {

            alert('Image size must be less than 5 MB.');

            this.value = '';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Show selected image immediately
        |--------------------------------------------------------------------------
        */

        const reader =
            new FileReader();


        reader.onload = function (event) {

            preview.src =
                event.target.result;

        };


        reader.readAsDataURL(file);

    });

});

</script>