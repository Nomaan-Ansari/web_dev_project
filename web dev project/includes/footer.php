<?php

$path_to_root = $path_to_root ?? '';
?>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <!-- Col 1: About School -->
        <div class="footer-col">
            <h4>Greenwood High School</h4>
            <p>Empowering young minds through academic rigor, moral values, and creative inquiry since 1995.</p>
            <p style="font-size: 0.85rem; color: #94a3b8;">Affiliated with Central Board of Secondary Education</p>
        </div>

        <!-- Col 2: Quick Navigation -->
        <div class="footer-col">
            <h4>Quick Links</h4>
            <p><a href="<?php echo $path_to_root; ?>index.html" style="color: #cbd5e1;">&rsaquo; Home</a></p>
            <p><a href="<?php echo $path_to_root; ?>index.html#about" style="color: #cbd5e1;">&rsaquo; About Us</a></p>
            <p><a href="<?php echo $path_to_root; ?>index.html#academics" style="color: #cbd5e1;">&rsaquo; Academic Curriculum</a></p>
            <p><a href="<?php echo $path_to_root; ?>index.html#notices" style="color: #cbd5e1;">&rsaquo; Public Notice Board</a></p>
            <p><a href="<?php echo $path_to_root; ?>login.html" style="color: #cbd5e1;">&rsaquo; Portal Login</a></p>
        </div>

        <!-- Col 3: Contact Details -->
        <div class="footer-col">
            <h4>Contact Details</h4>
            <p><strong>Address:</strong> 123 Education Lane, Knowledge City, State - 560001</p>
            <p><strong>Phone:</strong> +91 (555) 019-2834 / 2835</p>
            <p><strong>Email:</strong> info@greenwoodhigh.edu</p>
            <p><strong>Office Hours:</strong> Monday – Friday, 8:00 AM – 3:30 PM</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Greenwood High School. All rights reserved. | Simple Web Development Project</p>
    </div>
</footer>

<!-- Main Client-Side JavaScript -->
<script src="<?php echo $path_to_root; ?>js/script.js"></script>
</body>
</html>
