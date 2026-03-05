<div class="form-container">

	<h2 class="section-title">Register</h2>

	<form action="/register" method="POST">

		<div class="form-group">
			<label for="alias-input">Username</label>

			<?php
			if (isset($tab['username-taken']))
				echo "<p " . "style='color:red;'>" . $tab['username-taken'] . "</p>";
			else if (isset($tab['username-required']))
				echo "<p " . "style='color:red;'>" . $tab['username-required'] . "</p>";
						
			?>
			<input type="text" placeholder="faufaudu49" id="alias-input" name="username" required />
		</div>

		<div class="form-group">
			<label for="email-input">Email</label>

			<?php
			if (isset($tab['invalid-email']))
				echo "<p " . "style='color:red;'>" . $tab['invalid-email'] . "</p>";
			else if (isset($tab['email-taken']))
				echo "<p " . "style='color:red;'>" . $tab['email-taken'] . "</p>";
						
			?>
			<input type="email" placeholder="faufaudu49@gmail.com" id="email-input" name="email" required />
		</div>

		<div class="form-group">
			<label for="password-input">Password</label>

			<?php
			if (isset($tab['invalid-password']))
				echo "<p " . "style='color:red;'>" . $tab['invalid-password'] . "</p>";
			else if (isset($tab['password-required']))
				echo "<p " . "style='color:red;'>" . $tab['password-required'] . "</p>";
						
			?>
			<input type="password" placeholder="*******" id="password-input" name="password" required />
		</div>

		<button type="submit" id="register-button" class="button full-width">Register</button>

	</form>
</div>