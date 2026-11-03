<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="background-color: #0f172a; padding: 20px 24px; text-align: center;">
            <h2 style="color: #ffffff; margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.5px;">BUP ICT Alumni Association</h2>
        </div>
        <div style="padding: 24px 28px;">
            <p style="margin-top: 0; font-size: 15px; color: #334155;">Hello,</p>
            <p style="font-size: 15px; color: #334155; line-height: 1.5;">Your email verification code for the BUP ICT Alumni Association Platform is:</p>
            <div style="text-align: center; margin: 24px 0;">
                <span style="display: inline-block; font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #0f172a; background: #f1f5f9; padding: 12px 24px; border-radius: 6px; border: 1px dashed #cbd5e1;">{{ $code }}</span>
            </div>
            <p style="font-size: 14px; color: #64748b; line-height: 1.5;">This verification code will expire in <strong>{{ $expiryMinutes }} minutes</strong>. For security, never share this code with anyone.</p>
            <p style="font-size: 13px; color: #94a3b8; line-height: 1.5; margin-bottom: 0; border-top: 1px solid #f1f5f9; padding-top: 16px;">If you did not request this code, you can safely ignore this email — no account will be created without it.</p>
        </div>
    </div>
</body>
</html>
