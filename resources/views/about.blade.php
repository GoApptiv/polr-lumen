@extends('layouts.base')

@section('css')
<link rel='stylesheet' href='/css/about.css' />
<link rel='stylesheet' href='/css/effects.css' />
@endsection

@section('content')
<div class='about-contents'>
    <h2>About {{ env('APP_NAME') }}</h2>

    <p>
        <strong>{{ env('APP_NAME') }}</strong> is an internal tool developed and maintained by <strong>GoApptiv</strong> for use across various in-house services and platforms. 
        It is used to manage, track, and shorten URLs that power different modules within the GoApptiv ecosystem — including marketing communications, product services, and analytics.
    </p>

    <p>
        This service is accessible only to authorized GoApptiv team members and integrates securely with internal APIs and external services.
    </p>

    @if ($role == "admin")
    <dl>
        <p><strong>Build Information</strong></p>
        <dt>Version:</dt> <dd>{{ env('POLR_VERSION') }}</dd>
        <dt>Release Date:</dt> <dd>{{ env('POLR_RELDATE') }}</dd>
        <dt>App Installed:</dt> 
        <dd>{{ env('APP_NAME') }} on {{ env('APP_ADDRESS') }} (Generated: {{ env('POLR_GENERATED_AT') }})</dd>
    </dl>
    <p class="admin-note">
        You are seeing the build information above because you are logged in as an <strong>administrator</strong>. <br />
        To modify the content of this page, update the file: <code>resources/views/about.blade.php</code>.
    </p>
    @endif

    <hr>

    <p>
        {{ env('APP_NAME') }} is built on top of <a href="https://github.com/Cydrobolt/polr" target="_blank">Polr 2</a>, an open-source, minimalist link shortening platform. 
        While {{ env('APP_NAME') }} has been extensively customized for GoApptiv's internal use, its core functionality is powered by the Polr Project.
        <br />
        Learn more at <a href="https://project.polr.me" target="_blank">project.polr.me</a>. Polr is licensed under the GNU GPL License.
    </p>
</div>
@endsection
