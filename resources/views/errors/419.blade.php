@include('errors.layout', [
    'title'       => 'Session expired',
    'code'        => '419',
    'codeColor'   => 'text-amber-400',
    'heading'     => 'Session expired',
    'description' => 'Your session timed out. Reload the page and try again.',
])
