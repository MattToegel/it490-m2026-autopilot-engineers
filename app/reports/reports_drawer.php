<?php
// reports_drawer.php
// tad46: Sitewide FAB button + slide-in drawer for creating and editing reports
// tad46: Include this partial at the bottom of any page (before </body>) that
// tad46: needs the "post a report" affordance.
// tad46:
// tad46: Usage on the parent page:
// tad46:   1) set $currentPagePath (used as the return URL after post/edit)
// tad46:   2) optionally set $editingReport for pre-fill on edit (id, category,
// tad46:      terminal, comment_text) - reports.php and dashboard.php do this
// tad46:      when a ?report_edit=X flag is in the URL and the user owns it
// tad46:   3) include this file inside <body>

// tad46: guard against being loaded without a session
if (!isset($_SESSION))
{
    session_start();
}

$currentPagePath   = $currentPagePath ?? '/dashboard.php';
$drawerEditingId   = $editingReport['report_id']    ?? '';
$drawerEditingCat  = $editingReport['category']     ?? '';
$drawerEditingTerm = $editingReport['terminal']     ?? '';
$drawerEditingText = $editingReport['comment_text'] ?? '';
$drawerIsEditing   = !empty($drawerEditingId);

$categoryOptions = ['TSA', 'Bathroom', 'Accident', 'Food', 'Other'];
$terminalOptions = ['A', 'B', 'C', 'Unknown'];
?>

<!-- tad46: Floating Action Button  -->
<button
    type="button"
    id="report-fab"
    class="report-fab"
    aria-label="Post a report"
    aria-controls="report-drawer"
    aria-expanded="<?php echo $drawerIsEditing ? 'true' : 'false'; ?>"
>
    <img src="/assets/Reports.svg" alt="">
    <span class="report-fab-label">Post a report</span>
</button>

<!-- tad46: Backdrop scrim (dims content behind the drawer)  -->
<div
    id="report-scrim"
    class="report-scrim<?php echo $drawerIsEditing ? ' is-open' : ''; ?>"
    <?php echo $drawerIsEditing ? '' : 'hidden'; ?>
></div>

<!-- tad46: Slide-in Drawer  -->
<aside
    id="report-drawer"
    class="report-drawer<?php echo $drawerIsEditing ? ' is-open' : ''; ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="report-drawer-title"
    <?php echo $drawerIsEditing ? '' : 'hidden'; ?>
>
    <div class="report-drawer-head">
        <h2 id="report-drawer-title" class="report-drawer-title">
            <?php echo $drawerIsEditing ? 'Edit your report' : 'Post a report'; ?>
        </h2>
        <button
            type="button"
            id="report-drawer-close"
            class="report-drawer-close"
            aria-label="Close"
        >
            &times;
        </button>
    </div>

    <p class="report-drawer-subtitle">
        Share live conditions at EWR so other travelers can plan their trip.
    </p>

    <form
        method="post"
        action="<?php echo $drawerIsEditing ? '/reports/update_report.php' : '/reports/create_report.php'; ?>"
        class="report-drawer-form"
    >
        <!-- tad46: after POST, come back to the page the user was on -->
        <input
            type="hidden"
            name="return_to"
            value="<?php echo htmlspecialchars($currentPagePath, ENT_QUOTES, 'UTF-8'); ?>"
        >

        <?php if ($drawerIsEditing): ?>
            <input
                type="hidden"
                name="report_id"
                value="<?php echo (int)$drawerEditingId; ?>"
            >
        <?php endif; ?>

        <div class="form-group">
            <label for="drawer-category">Category</label>
            <select id="drawer-category" name="category" required>
                <option value="">Choose one</option>
                <?php foreach ($categoryOptions as $opt): ?>
                    <option
                        value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php if (strcasecmp($drawerEditingCat, $opt) === 0): ?>selected<?php endif; ?>
                    >
                        <?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="drawer-terminal">Terminal</label>
            <select id="drawer-terminal" name="terminal">
                <option value="">Any</option>
                <?php foreach ($terminalOptions as $opt): ?>
                    <option
                        value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php if (strcasecmp($drawerEditingTerm, $opt) === 0): ?>selected<?php endif; ?>
                    >
                        Terminal <?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="drawer-comment">What's happening?</label>
            <textarea
                id="drawer-comment"
                name="comment_text"
                rows="6"
                maxlength="2000"
                placeholder="Describe what other travelers should know."
                required
            ><?php echo htmlspecialchars($drawerEditingText, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="report-drawer-actions">
            <button type="submit" class="report-drawer-submit">
                <?php echo $drawerIsEditing ? 'Save changes' : 'Post report'; ?>
            </button>

            <?php if ($drawerIsEditing): ?>
                <a
                    href="<?php echo htmlspecialchars($currentPagePath, ENT_QUOTES, 'UTF-8'); ?>"
                    class="report-drawer-cancel"
                >
                    Cancel
                </a>
            <?php endif; ?>
        </div>
    </form>
</aside>

<link rel="stylesheet" href="/public/reports_drawer.css">
<script src="/public/reports_drawer.js" defer></script>
