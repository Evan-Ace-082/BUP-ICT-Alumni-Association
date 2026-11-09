@extends('layouts.app')

@section('title', 'Contact — BUP ICT Alumni Association')

@section('content')
  {{-- Compact Hero Header --}}
  <section class="hero hero-compact">
    <div class="container">
      <div class="eyebrow mb-2">Contact</div>
      <h1 class="mb-1">Get in touch</h1>
      <p class="text-muted-glass small mb-0">Reach the association executive desk, verify credentials, or inquire about membership.</p>
    </div>
  </section>

  <section class="section py-4">
    <div class="container">
      <div class="row gy-4">
        {{-- Left Column: Department & Contact Information --}}
        <div class="col-lg-5">
          <div class="card-feature mb-3">
            <span class="section-eyebrow mb-2">Office Location</span>
            <h2 class="h5 mb-2">Department of ICT</h2>
            <p class="text-muted-slate mb-1">Bangladesh University of Professionals (BUP)</p>
            <p class="text-muted-slate mb-3">Mirpur Cantonment, Dhaka-1216, Bangladesh</p>
            
            <div class="pt-3 border-top">
              <div class="mb-2">
                <strong class="text-dark small">Email:</strong>
                <a href="mailto:bupictalumniassociation26@gmail.com" class="text-break small d-block">bupictalumniassociation26@gmail.com</a>
              </div>
              <div class="mb-2">
                <strong class="text-dark small">Telephone:</strong>
                <span class="text-muted-slate small d-block">+880 2-8000441-5 (Ext: ICT Desk)</span>
              </div>
              <div>
                <strong class="text-dark small">Hours:</strong>
                <span class="text-muted-slate small d-block">Sunday &ndash; Thursday: 9:00 AM &ndash; 4:00 PM</span>
              </div>
            </div>
          </div>

          <div class="card-feature">
            <span class="section-eyebrow mb-2">Connect Directly</span>
            <div class="d-flex gap-2 flex-wrap">
              <a href="https://www.facebook.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-ink btn-sm d-inline-flex align-items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span>Facebook Group</span>
              </a>
              <a href="https://www.linkedin.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-ink btn-sm d-inline-flex align-items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                <span>LinkedIn Community</span>
              </a>
            </div>
          </div>
        </div>

        {{-- Right Column: Styled Interactive Contact Form --}}
        <div class="col-lg-7">
          <div class="card-feature h-100 p-4">
            <span class="section-eyebrow mb-1">Send a Message</span>
            <h2 class="h5 mb-3">General Inquiries & Feedback</h2>
            
            <form onsubmit="event.preventDefault(); alert('Thank you! Your message has been received by the BUP ICT Alumni office.'); this.reset();">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold" for="contact_name">Full Name</label>
                  <input type="text" id="contact_name" class="form-control" placeholder="e.g. Tanzim Ahmed" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold" for="contact_email">Email Address</label>
                  <input type="email" id="contact_email" class="form-control" placeholder="e.g. name@example.com" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold" for="contact_batch">Batch or Student ID (Optional)</label>
                  <input type="text" id="contact_batch" class="form-control" placeholder="e.g. Batch 04 / 180105">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold" for="contact_subject">Subject</label>
                  <input type="text" id="contact_subject" class="form-control" placeholder="e.g. Verification Assistance" required>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold" for="contact_message">Message</label>
                  <textarea id="contact_message" class="form-control" rows="4" placeholder="How can the association support you?" required></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-brass px-4">Send Message</button>
                  <small class="text-muted-slate ms-2">Official desk replies within 1-2 business days.</small>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
