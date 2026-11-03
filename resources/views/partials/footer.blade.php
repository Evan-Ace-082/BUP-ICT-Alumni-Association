<footer class="site-footer mt-auto">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <div class="footer-brand mb-2">BUP ICT Alumni Association</div>
        <p class="small mb-0">Department of ICT<br>Bangladesh University of Professionals</p>
      </div>
      <div class="col-md-4">
        <h6 class="text-white-50 small text-uppercase mb-3" style="letter-spacing:.08em;">Explore</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="{{ route('batches.index') }}">Batches</a></li>
          <li class="mb-2"><a href="{{ route('alumni.directory') }}">Alumni Directory</a></li>
          <li class="mb-2"><a href="{{ route('alumni.network') }}">Alumni Network</a></li>
          <li class="mb-2"><a href="{{ route('committees.index') }}">Executive Committee</a></li>
          <li class="mb-2"><a href="{{ route('elections.results.index') }}">Election Results</a></li>
          <li class="mb-2"><a href="{{ route('events.index') }}">Events</a></li>
          <li class="mb-2"><a href="{{ route('about') }}">About the Association</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="text-white-50 small text-uppercase mb-3" style="letter-spacing:.08em;">Contact</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="{{ route('contact') }}">Get in touch</a></li>
          <li class="mb-2">Department of ICT, BUP</li>
          <li class="mb-2">Mirpur Cantonment, Dhaka</li>
          <li class="mb-2"><a href="mailto:bupictalumniassociation26@gmail.com" class="text-break">bupictalumniassociation26@gmail.com</a></li>
        </ul>
      </div>
    </div>
    <hr class="mt-4" style="border-color: rgba(255,255,255,.1);">
    <p class="small mb-0">&copy; {{ date('Y') }} BUP ICT Alumni Association. Built by the Department of ICT.</p>
  </div>
</footer>
