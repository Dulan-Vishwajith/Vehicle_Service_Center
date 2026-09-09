<?php

$offerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$offer = [
    'card_class' => 'dark-card',
    'icon' => '🔥',
    'offer_type' => 'SPECIAL OFFER',
    'title' => '',
    'discount' => '',
    'description' => '',
    'valid_text' => '',
    'link' => 'book-appointment.php',
    'status' => 1
];

$isEdit = false;
$formError = '';

if ($offerId) {

    $stmt = $pdo->prepare(
        "SELECT * FROM offers WHERE id = ? LIMIT 1"
    );

    $stmt->execute([$offerId]);

    $existing = $stmt->fetch();

    if ($existing) {
        $offer = array_merge($offer, $existing);
        $isEdit = true;
    } else {
        $formError = 'Offer not found.';
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_offer'])
) {

    $id = (int) ($_POST['id'] ?? 0);

    foreach (
        [
            'card_class',
            'icon',
            'offer_type',
            'title',
            'discount',
            'description',
            'valid_text',
            'link'
        ] as $f
    ) {
        $offer[$f] = trim($_POST[$f] ?? '');
    }

    $offer['status'] = isset($_POST['status']) ? 1 : 0;

    if (
        $offer['title'] === '' ||
        $offer['discount'] === '' ||
        $offer['description'] === ''
    ) {

        $formError = 'Please complete the required fields.';

    } else {

        if ($id > 0) {

            $stmt = $pdo->prepare(
                "UPDATE offers
                SET
                    card_class = ?,
                    icon = ?,
                    offer_type = ?,
                    title = ?,
                    discount = ?,
                    description = ?,
                    valid_text = ?,
                    link = ?,
                    status = ?
                WHERE id = ?"
            );

        } else {

            $stmt = $pdo->prepare(
                "INSERT INTO offers
                (
                    card_class,
                    icon,
                    offer_type,
                    title,
                    discount,
                    description,
                    valid_text,
                    link,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
        }

        $params = [
            $offer['card_class'],
            $offer['icon'],
            $offer['offer_type'],
            $offer['title'],
            $offer['discount'],
            $offer['description'],
            $offer['valid_text'],
            $offer['link'],
            $offer['status']
        ];

        if ($id > 0) {
            $params[] = $id;
        }

        $stmt->execute($params);

        header(
            'Location: ?page=offers&message=' .
            urlencode(
                $id
                    ? 'Offer updated.'
                    : 'Offer created.'
            )
        );

        exit;
    }
}

?>

<div class="panel-header">

    <h2>
        <?= $isEdit ? 'Edit Offer' : 'Create Offer' ?>
    </h2>

</div>


<?php if ($formError): ?>

    <div class="message error">
        <?= htmlspecialchars($formError) ?>
    </div>

<?php endif; ?>


<form
    method="post"
    class="management-form offer-management-form"
>

    <input
        type="hidden"
        name="id"
        value="<?= (int) $offerId ?>"
    >


    <label>

        Card Class

        <input
            name="card_class"
            value="<?= htmlspecialchars($offer['card_class']) ?>"
        >

    </label>


    <label>

        Icon

        <input
            name="icon"
            value="<?= htmlspecialchars($offer['icon']) ?>"
        >

    </label>


    <label>

        Offer Type

        <input
            name="offer_type"
            value="<?= htmlspecialchars($offer['offer_type']) ?>"
        >

    </label>


    <label>

        Offer Name / Title *

        <input
            name="title"
            required
            value="<?= htmlspecialchars($offer['title']) ?>"
        >

    </label>


    <label>

        Discount *

        <input
            name="discount"
            required
            value="<?= htmlspecialchars($offer['discount']) ?>"
            placeholder="15% OFF"
        >

    </label>


    <label>

        Description *

        <textarea
            name="description"
            required
        ><?= htmlspecialchars($offer['description']) ?></textarea>

    </label>


    <label>

        Valid Text

        <input
            name="valid_text"
            value="<?= htmlspecialchars($offer['valid_text']) ?>"
            placeholder="Valid until ..."
        >

    </label>


    <label>

        Link

        <input
            name="link"
            value="<?= htmlspecialchars($offer['link']) ?>"
        >

    </label>


    <label class="checkbox-label">

        <input
            type="checkbox"
            name="status"
            <?= $offer['status'] ? 'checked' : '' ?>
        >

        Active

    </label>


    <div class="form-actions">

        <button
            class="btn btn-primary"
            name="save_offer"
            type="submit"
        >
            Save Offer
        </button>


        <a
            class="btn"
            href="?page=offers"
        >
            Cancel
        </a>

    </div>

</form>