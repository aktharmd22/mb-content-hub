@include('errors.layout', [
    'title'       => 'Forbidden',
    'code'        => '403',
    'codeColor'   => 'text-amber-400',
    'heading'     => 'Access denied',
    'description' => "You don't have permission to view this page.",
])
