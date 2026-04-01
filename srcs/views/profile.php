<div class="form-wrapper">
	<div class="form-card">

		<h1 style="text-align: center; padding-bottom: 20px;">My profile</h1>

		<?php if (isset($_GET['error'])): ?>

			<div style="color: #d9534f; background-color: #f2dede; border: 1px solid #ebccd1; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 20px;">
				<?php
					if ($_GET['error'] === 'username_taken') {
						echo "This username is already taken. Please choose another one.";
					} elseif ($_GET['error'] === 'email_taken') {
						echo "This email is already associated with an account.";
					} elseif ($_GET['error'] === 'invalid_email') {
						echo "The email format is invalid.";
					} elseif ($_GET['error'] === 'weak_password') {
						echo "Your password must contain at least 8 characters, including a letter and a number.";
					} else {
						echo "An error occurred. Please try again.";
					}
				?>
			</div>
		<?php endif; ?>
		<form action="/profile" method="POST">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken()) ?>">

			<div class="form-group">
				<label for="username-input">Username</label>
				<input
					type="text"
					id="alias-input"
					name="username"
					placeholder="faufaudu49"
					value="<?= htmlspecialchars($fetchData['username'] ?? '') ?>"
					disabled
				/>
			</div>

			<div class="form-group">
				<label for="email-input">Email</label>
				<input
					type="email"
					id="email-input"
					name="email"
					placeholder="you@example.com"
					value="<?= htmlspecialchars($fetchData['email'] ?? '') ?>"
					disabled
				/>
			</div>

			<div class="form-group">
				<label for="notification-choice">Allow email notifications when receiving a new comment?</label>
				<input
				type="checkbox"
				id="email-notification"
				name="notification"
				value="1"
				<?= (isset($fetchData['email_notifications']) && $fetchData['email_notifications'] == 0) ? '' : 'checked' ?> disabled />
			</div>

			<button type="button" id="button-update">UPDATE MY INFORMATIONS</button>

		</form>

		<div class="form-divider"></div>
		<form action="/profile" method="POST">

			<div class="form-group">
				<label for="password-input">Password</label>
				<input
					type="password"
					id="password-input"
					name="password"
					placeholder="********"
					disabled
				/>
			</div>
			<div class="form-group" id="div-confirm-password" style="display:none;">
				<label for="password-input">Confirm password</label>
				<input
					type="password"
					id="password-confirm-input"
					name="password"
					placeholder="Min. 8 alphanumeric characters"
				/>
			</div>
			<button type="button" id="button-password">CHANGE MY PASSWORD</button>



		</form>
	</div>
</div>

<script>

	const username = document.getElementById('alias-input');
	const email = document.getElementById('email-input');
	const password = document.getElementById('password-input');
	const passwordConfirm = document.getElementById('div-confirm-password');
	const checkboxNotification = document.getElementById('email-notification');
	const updateButton = document.getElementById('button-update');
	const passwordButton = document.getElementById('button-password');

	updateButton.addEventListener('click', function(event) {

		if (username.disabled === true) {
			event.preventDefault();
			username.disabled = false;
			email.disabled = false;
			checkboxNotification.disabled = false;
			updateButton.innerHTML = "SAVE CHANGES";
			updateButton.setAttribute('type', 'submit');
		}
	});

	passwordButton.addEventListener('click', function(event) {
		if (password.disabled === true) {
			event.preventDefault();
			password.disabled = false;
			passwordConfirm.style.display = 'block';
	
			passwordButton.innerHTML = "SAVE PASSWORD";
			passwordButton.setAttribute('type', 'submit');

		}
	})





</script>