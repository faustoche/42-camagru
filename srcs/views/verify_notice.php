<div class="form-wrapper">
	<div class="form-card" style="text-align: center;">
		<h1 style="margin-bottom: 10px;">Check your inbox! 📧</h1>
		
		<p style="margin: 20px 0; font-size: 1.1rem; color: #262626;">
			We've sent a verification link to <br>
			<b style="color: #87CEEB;"><?= htmlspecialchars($email) ?></b>
		</p>
		
		<p style="color: #8e8e8e; font-size: 0.95rem;">
			Please click the link in that email to activate your account before logging in.
		</p>
		
		<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #efefef;">
			<p style="font-size: 0.85rem; color: #8e8e8e; margin-bottom: 15px;">Didn't receive the email?</p>
			
			<button type="button" id="btn-resend" style="width: auto; padding: 10px 25px; margin: 0 auto; display: block; background-color: #87CEEB; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
				RESEND EMAIL
			</button>
			
			<span id="resend-msg" style="display: none; margin-top: 15px; font-size: 0.9rem; color: #28a745; font-weight: bold;">
				✓ Email resent successfully!
			</span>
		</div>
		
		<div style="margin-top: 30px;">
			<a href="/login" style="color: #87CEEB; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Back to Login</a>
		</div>
	</div>
</div>

<script>
	document.getElementById('btn-resend').addEventListener('click', function() {
		const btn = this;
		btn.disabled = true;
		btn.style.opacity = '0.5';
		btn.innerHTML = "SENDING...";
		
		fetch('/resend-verification', {
			method: 'POST'
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				btn.innerHTML = "EMAIL SENT";
				document.getElementById('resend-msg').style.display = 'block';
			} else {
				btn.innerHTML = "RESEND EMAIL";
				btn.disabled = false;
				btn.style.opacity = '1';
				alert(data.message || "An error occurred");
			}
		})
		.catch(error => {
			console.error("Erreur:", error);
			btn.innerHTML = "RESEND EMAIL";
			btn.disabled = false;
			btn.style.opacity = '1';
		});
	});
</script>