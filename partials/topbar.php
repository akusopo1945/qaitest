<?php
declare(strict_types=1);

$activePage = $activePage ?? 'home';
?>
<nav class="topbar" aria-label="Primary navigation" data-testid="topbar">
    <a class="brand" href="/" aria-label="Qaitest home">
        <span class="brand-mark" aria-hidden="true"></span>
        <span>Qaitest</span>
    </a>
    <div class="topbar-links">
        <a class="topbar-link<?php echo $activePage === 'home' ? ' is-active' : ''; ?>" href="/">Home</a>
        <a class="topbar-link<?php echo $activePage === 'entries' ? ' is-active' : ''; ?>" href="/entries.php">Entries</a>
        <a class="topbar-link<?php echo $activePage === 'qa' ? ' is-active' : ''; ?>" href="/qa.php">QA</a>
        <a class="topbar-link<?php echo $activePage === 'about' ? ' is-active' : ''; ?>" href="/about.php">About</a>
    </div>
</nav>
