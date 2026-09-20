<?php

/*
|--------------------------------------------------------------------------
| SERVICE FORM
|--------------------------------------------------------------------------
| Create / Edit service
|
| SERVICE IMAGE
|--------------------------------------------------------------------------
| Physical location:
|     public/images/services/
|
| Database value:
|     images/services/filename.jpg
|
| Required resolution:
|     1200 x 675 pixels
|
| Allowed:
|     JPG
|     JPEG
|     PNG
|     WEBP
|
| Maximum:
|     5 MB
|--------------------------------------------------------------------------
*/


/* =========================================================
   IMAGE CONFIGURATION
   ========================================================= */

$serviceImageDirectory =
    __DIR__ . '/../../../public/images/services/';

$serviceImageDatabasePath =
    'images/services/';

$maxImageSize =
    5 * 1024 * 1024; // 5 MB

$requiredImageWidth =
    1200;

$requiredImageHeight =
    675;

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];


/* =========================================================
   DELETE SERVICE IMAGE FUNCTION
   ========================================================= */

function deleteServiceImage($imagePath)
{
    if (
        empty($imagePath) ||
        !is_string($imagePath)
    ) {
        return;
    }


    /*
     * Only allow files inside:
     *
     * images/services/
     */

    if (
        strpos(
            $imagePath,
            'images/services/'
        ) !== 0
    ) {
        return;
    }


    /*
     * Prevent directory traversal.
     */

    if (
        strpos(
            $imagePath,
            '..'
        ) !== false
    ) {
        return;
    }


    /*
     * Convert database path into
     * physical server path.
     */

    $fullPath =
        __DIR__ .
        '/../../../public/' .
        $imagePath;


    if (is_file($fullPath)) {

        @unlink($fullPath);

    }
}


/* =========================================================
   SERVICE ID
   ========================================================= */

$serviceId =
    (int) ($_GET['id'] ?? 0);


/* =========================================================
   DEFAULT SERVICE DATA
   ========================================================= */

$service = [

    'service_name' =>
        '',

    'category' =>
        'maintenance',

    'description' =>
        '',

    'price' =>
        '',

    'duration' =>
        '',

    'duration_minutes' =>
        '',

    'icon' =>
        '🔧',

    'image' =>
        null,

    'status' =>
        1

];


$formError =
    '';

$isEdit =
    false;


/* =========================================================
   LOAD EXISTING SERVICE
   ========================================================= */

if ($serviceId > 0) {

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM services
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $serviceId
        ]);

        $existing =
            $stmt->fetch();


        if ($existing) {

            $service =
                array_merge(
                    $service,
                    $existing
                );

            $isEdit =
                true;

        } else {

            $formError =
                "That service could not be found.";

        }

    } catch (PDOException $e) {

        $formError =
            "Unable to load this service.";

    }
}


/* =========================================================
   HANDLE FORM SUBMISSION
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_service'])
) {


    /* =====================================================
       GET FORM VALUES
       ===================================================== */

    $serviceName =
        trim(
            $_POST['service_name'] ?? ''
        );


    $category =
        trim(
            $_POST['category'] ?? ''
        );


    $description =
        trim(
            $_POST['description'] ?? ''
        );


    $price =
        (float) (
            $_POST['price'] ?? 0
        );


    $duration =
        trim(
            $_POST['duration'] ?? ''
        );


    $durationMinutes =
        (int) (
            $_POST['duration_minutes'] ?? 0
        );


    $icon =
        trim(
            $_POST['icon'] ?? '🔧'
        );


    $status =
        isset($_POST['status'])
            ? (int) $_POST['status']
            : 1;


    $removeImage =
        isset($_POST['remove_image']) &&
        $_POST['remove_image'] === '1';


    /*
     * Keep entered values if validation fails.
     */

    $service =
        array_merge(
            $service,
            $_POST
        );


    $service['status'] =
        $status;


    /* =====================================================
       VALIDATION
       ===================================================== */

    if (
        $serviceName === '' ||
        $category === '' ||
        $description === '' ||
        $duration === ''
    ) {

        $formError =
            "Please fill in all required fields.";

    } elseif ($price <= 0) {

        $formError =
            "Please enter a valid price greater than zero.";

    } elseif ($durationMinutes <= 0) {

        $formError =
            "Please enter the duration in minutes.";

    } elseif (
        !in_array(
            $category,
            [
                'maintenance',
                'repair',
                'inspection'
            ],
            true
        )
    ) {

        $formError =
            "Invalid service category.";

    }


    /* =====================================================
       EXISTING IMAGE
       ===================================================== */

    $oldImage =
        $service['image'] ?? null;


    /*
     * New uploaded image path.
     */

    $newImagePath =
        null;


    /*
     * Physical path of newly uploaded image.
     * Used for cleanup if database save fails.
     */

    $newImageFullPath =
        null;


    /* =====================================================
       IMAGE UPLOAD
       ===================================================== */

    if (
        $formError === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        $uploadedFile =
            $_FILES['image'];


        /* =================================================
           CHECK UPLOAD ERROR
           ================================================= */

        if (
            $uploadedFile['error'] !==
            UPLOAD_ERR_OK
        ) {

            $formError =
                "The image upload failed.";

        }


        /* =================================================
           CHECK FILE SIZE
           ================================================= */

        elseif (
            $uploadedFile['size'] >
            $maxImageSize
        ) {

            $formError =
                "Image size must not exceed 5 MB.";

        }


        /* =================================================
           CHECK REAL UPLOAD
           ================================================= */

        elseif (
            !is_uploaded_file(
                $uploadedFile['tmp_name']
            )
        ) {

            $formError =
                "Invalid image upload.";

        }


        /* =================================================
           CHECK IMAGE
           ================================================= */

        else {

            $imageInfo =
                @getimagesize(
                    $uploadedFile['tmp_name']
                );


            if (
                $imageInfo === false
            ) {

                $formError =
                    "The uploaded file is not a valid image.";

            }


            /* =============================================
               CHECK EXACT RESOLUTION
               ============================================= */

            elseif (
                (int) $imageInfo[0] !==
                    $requiredImageWidth ||

                (int) $imageInfo[1] !==
                    $requiredImageHeight
            ) {

                $formError =
                    "Service image must be exactly 1200 × 675 pixels.";

            }


            /* =============================================
               CHECK MIME TYPE
               ============================================= */

            if (
                $formError === ''
            ) {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );


                $mimeType =
                    $finfo->file(
                        $uploadedFile['tmp_name']
                    );


                if (
                    !isset(
                        $allowedMimeTypes[
                            $mimeType
                        ]
                    )
                ) {

                    $formError =
                        "Invalid image format. Please use JPG, JPEG, PNG or WEBP.";

                }

            }


            /* =============================================
               CREATE IMAGE DIRECTORY
               ============================================= */

            if (
                $formError === ''
            ) {

                if (
                    !is_dir(
                        $serviceImageDirectory
                    )
                ) {

                    if (
                        !mkdir(
                            $serviceImageDirectory,
                            0755,
                            true
                        )
                    ) {

                        $formError =
                            "Unable to create the service image directory.";

                    }

                }

            }


            /* =============================================
               GENERATE UNIQUE FILE NAME
               ============================================= */

            if (
                $formError === ''
            ) {

                $extension =
                    $allowedMimeTypes[
                        $mimeType
                    ];


                try {

                    $randomPart =
                        bin2hex(
                            random_bytes(8)
                        );

                } catch (Exception $e) {

                    $randomPart =
                        uniqid();

                }


                $fileName =
                    'service_' .
                    time() .
                    '_' .
                    $randomPart .
                    '.' .
                    $extension;


                /*
                 * Database path
                 */

                $newImagePath =
                    $serviceImageDatabasePath .
                    $fileName;


                /*
                 * Physical path
                 */

                $newImageFullPath =
                    $serviceImageDirectory .
                    $fileName;


                /* =========================================
                   MOVE FILE
                   ========================================= */

                if (
                    !move_uploaded_file(
                        $uploadedFile['tmp_name'],
                        $newImageFullPath
                    )
                ) {

                    $formError =
                        "Unable to save the uploaded image.";

                    $newImagePath =
                        null;

                    $newImageFullPath =
                        null;

                }

            }

        }

    }


    /* =====================================================
       SAVE TO DATABASE
       ===================================================== */

    if (
        $formError === ''
    ) {

        try {


            /* =================================================
               DETERMINE FINAL IMAGE
               ================================================= */

            if (
                $newImagePath !== null
            ) {

                /*
                 * New photo uploaded.
                 */

                $finalImage =
                    $newImagePath;

            } elseif (
                $removeImage &&
                !empty($oldImage)
            ) {

                /*
                 * User selected remove image.
                 */

                $finalImage =
                    null;

            } else {

                /*
                 * Keep existing image.
                 */

                $finalImage =
                    $oldImage;

            }


            /* =================================================
               UPDATE EXISTING SERVICE
               ================================================= */

            if ($isEdit) {


                $stmt =
                    $pdo->prepare("
                        UPDATE services
                        SET
                            service_name = ?,
                            category = ?,
                            description = ?,
                            price = ?,
                            duration = ?,
                            duration_minutes = ?,
                            icon = ?,
                            image = ?,
                            status = ?
                        WHERE id = ?
                    ");


                $stmt->execute([

                    $serviceName,

                    $category,

                    $description,

                    $price,

                    $duration,

                    $durationMinutes,

                    $icon,

                    $finalImage,

                    $status,

                    $serviceId

                ]);


            }


            /* =================================================
               CREATE NEW SERVICE
               ================================================= */

            else {


                $stmt =
                    $pdo->prepare("
                        INSERT INTO services
                        (
                            service_name,
                            category,
                            description,
                            price,
                            duration,
                            duration_minutes,
                            icon,
                            image,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");


                $stmt->execute([

                    $serviceName,

                    $category,

                    $description,

                    $price,

                    $duration,

                    $durationMinutes,

                    $icon,

                    $finalImage,

                    $status

                ]);

            }


            /* =================================================
               DELETE OLD IMAGE
               ================================================= */

            if (
                $isEdit &&
                !empty($oldImage) &&
                $oldImage !== $finalImage
            ) {

                deleteServiceImage(
                    $oldImage
                );

            }


            /* =================================================
               SUCCESS REDIRECT
               ================================================= */

            header(
                "Location: ?page=services"
            );

            exit();


        } catch (PDOException $e) {


            /*
             * Database failed.
             *
             * Delete newly uploaded file so
             * an orphan image is not left behind.
             */

            if (
                !empty($newImageFullPath) &&
                is_file(
                    $newImageFullPath
                )
            ) {

                @unlink(
                    $newImageFullPath
                );

            }


            $formError =
                "Unable to save this service. Please try again.";

        }

    }

}

?>


<!-- =========================================================
     HEADER
     ========================================================= -->

<div class="panel-header">

    <h2>

        <?= $isEdit
            ? 'Edit Service'
            : 'New Service' ?>

    </h2>


    <a href="?page=services">
        &larr; Back to Services
    </a>

</div>


<!-- =========================================================
     ERROR MESSAGE
     ========================================================= -->

<?php if ($formError): ?>

    <div class="admin-message error">

        <?= htmlspecialchars(
            $formError,
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

<?php endif; ?>


<!-- =========================================================
     SERVICE FORM
     ========================================================= -->

<form
    method="post"
    class="admin-form"
    enctype="multipart/form-data"
>


    <div class="admin-form-grid">


        <!-- =================================================
             SERVICE NAME
             ================================================= -->

        <div class="admin-form-group">

            <label for="service_name">
                Service Name
            </label>

            <input
                type="text"
                id="service_name"
                name="service_name"
                value="<?= htmlspecialchars(
                    $service['service_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                required
            >

        </div>


        <!-- =================================================
             CATEGORY
             ================================================= -->

        <div class="admin-form-group">

            <label for="category">
                Category
            </label>

            <select
                id="category"
                name="category"
                required
            >

                <?php foreach (
                    [
                        'maintenance',
                        'repair',
                        'inspection'
                    ] as $cat
                ): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $cat,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        <?= $service['category'] === $cat
                            ? 'selected'
                            : '' ?>
                    >

                        <?= ucfirst($cat) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- =================================================
             DESCRIPTION
             ================================================= -->

        <div class="admin-form-group admin-form-full">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="3"
                required
            ><?= htmlspecialchars(
                $service['description'],
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>

        </div>


        <!-- =================================================
             PRICE
             ================================================= -->

        <div class="admin-form-group">

            <label for="price">
                Price (Rs.)
            </label>

            <input
                type="number"
                id="price"
                step="0.01"
                min="0"
                name="price"
                value="<?= htmlspecialchars(
                    (string) $service['price'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                required
            >

        </div>


        <!-- =================================================
             DURATION
             ================================================= -->

        <div class="admin-form-group">

            <label for="duration">
                Duration Label
            </label>

            <input
                type="text"
                id="duration"
                name="duration"
                placeholder="e.g. 1 Hour"
                value="<?= htmlspecialchars(
                    $service['duration'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                required
            >

        </div>


        <!-- =================================================
             DURATION MINUTES
             ================================================= -->

        <div class="admin-form-group">

            <label for="duration_minutes">
                Duration (Minutes)
            </label>

            <input
                type="number"
                id="duration_minutes"
                name="duration_minutes"
                min="1"
                value="<?= htmlspecialchars(
                    (string) $service['duration_minutes'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                required
            >

        </div>


        <!-- =================================================
             FALLBACK EMOJI
             ================================================= -->

        <div class="admin-form-group">

            <label for="icon">
                Fallback Icon / Emoji
            </label>

            <input
                type="text"
                id="icon"
                name="icon"
                maxlength="10"
                value="<?= htmlspecialchars(
                    $service['icon'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <small class="admin-form-help">

                This emoji is displayed when no service
                photo is available.

            </small>

        </div>


        <!-- =================================================
             SERVICE PHOTO
             ================================================= -->

        <div class="admin-form-group admin-form-full">

            <label for="image">
                Service Photo
            </label>


            <!-- =============================================
                 CURRENT IMAGE
                 ============================================= -->

            <?php if (
                !empty($service['image'])
            ): ?>


                <div class="service-image-preview">

                    <img
                        src="../public/<?= htmlspecialchars(
                            $service['image'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="Current service photo"
                        width="1200"
                        height="675"
                    >


                    <div class="service-image-preview-info">

                        <strong>
                            Current Service Photo
                        </strong>

                        <span>
                            1200 × 675 px
                        </span>

                    </div>

                </div>


                <!-- =========================================
                     REMOVE IMAGE
                     ========================================= -->

                <label class="service-image-remove">

                    <input
                        type="checkbox"
                        name="remove_image"
                        value="1"
                    >

                    Remove current photo and use
                    the fallback emoji.

                </label>


            <?php endif; ?>


            <!-- =============================================
                 UPLOAD
                 ============================================= -->

            <input
                type="file"
                id="image"
                name="image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >


            <!-- =============================================
                 IMAGE REQUIREMENTS
                 ============================================= -->

            <div class="service-image-requirements">

                <strong>
                    Service Photo Requirements
                </strong>


                <ul>

                    <li>
                        Resolution:
                        <strong>
                            1200 × 675 pixels
                        </strong>
                    </li>

                    <li>
                        Aspect ratio:
                        <strong>
                            16:9
                        </strong>
                    </li>

                    <li>
                        Allowed formats:
                        <strong>
                            JPG, JPEG, PNG, WEBP
                        </strong>
                    </li>

                    <li>
                        Maximum file size:
                        <strong>
                            5 MB
                        </strong>
                    </li>

                    <li>
                        Use a clear horizontal photo
                        related to the service.
                    </li>

                </ul>

            </div>

        </div>


        <!-- =================================================
             STATUS
             ================================================= -->

        <div class="admin-form-group">

            <label for="status">
                Status
            </label>

            <select
                id="status"
                name="status"
            >

                <option
                    value="1"
                    <?= $service['status']
                        ? 'selected'
                        : '' ?>
                >
                    Active
                </option>

                <option
                    value="0"
                    <?= !$service['status']
                        ? 'selected'
                        : '' ?>
                >
                    Inactive
                </option>

            </select>

        </div>


    </div>


    <!-- =================================================
         ACTIONS
         ================================================= -->

    <div class="admin-form-actions">

        <a
            href="?page=services"
            class="admin-cancel-btn"
        >
            Cancel
        </a>


        <button
            type="submit"
            name="save_service"
            value="1"
            class="admin-save-btn"
        >

            <?= $isEdit
                ? 'Save Changes'
                : 'Create Service' ?>

        </button>

    </div>


</form>