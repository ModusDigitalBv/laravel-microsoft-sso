<?php

namespace ModusDigital\LaravelMicrosoftSso\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\{text, select, confirm};

class SsoPromptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modus:generate-sso';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the SSO configuration file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Check if the sso.php configuration file already exists
        $configPath = config_path(path: 'sso.php');
        if (file_exists($configPath)) {
            $isPresent = confirm(
                label: 'The SSO configuration file already exists, do you want to overwrite it?',
                default: false
            );

            if (!$isPresent) {
                $this->info(string: "Operation aborted, run the command again to generate the configuration file.");

                exit(1);
            }
        }

        $options = [
            'strict' => select(
                label: 'Do you want to enable strict mode?',
                options: ['yes', 'no'],
                default: 'yes'
            ),
            'base_url' => text(
                label: 'What is the base URL of your application?',
                default: 'http://localhost'
            ),
            'sp_entity_id' => text(
                label: 'What is the SP entity ID?',
                default: '/sso'
            ),
            'sp_acs_url' => text(
                label: 'What is the SP ACS URL?',
                default: 'http://localhost/sso/acs'
            ),
            'sp_service_name' => text(
                label: 'What is the SP service name?',
                default: 'Company name SSO'
            ),
            'sp_service_description' => text(
                label: 'What is the SP service description?',
                default: 'Single Sign-On'
            ),
            'sp_sls' => text(
                label: 'What is the SP SLS (single logout service) URL?',
                default: 'http://localhost/sso/sls'
            ),
            'idp_entity_id' => text(
                label: 'What is the IDP entity ID?',
                default: 'http://localhost/sso/idp'
            ),
            'idp_sso_url' => text(
                label: 'What is the IDP SSO URL?',
                default: 'http://localhost/sso/idp/sso'
            ),
            'idp_sls_url' => text(
                label: 'What is the IDP SLS URL?',
                default: 'http://localhost/sso/idp/sls'
            ),
            'idp_x509_cert' => text(
                label: 'What is the IDP x509 cert?',
                default: 'Base64_encoded_certificate'
            ),
            'contact_technical_name' => text(
                label: 'What is the contact name for technical support?',
                default: 'John Doe'
            ),
            'contact_technical_email' => text(
                label: 'What is the contact email for technical support?',
                default: 'john.doe@example.net'
            ),
            'contact_support_name' => text(
                label: 'What is the contact name for support?',
                default: 'John Doe'
            ),
            'contact_support_email' => text(
                label: 'What is the contact email for support?',
                default: 'john.doe@example.net'
            ),
            'organization_name' => text(
                label: 'What is the organization name?',
                default: 'Company name'
            ),
            'organization_display_name' => text(
                label: 'What is the organization display name?',
                default: 'Company name'
            ),
            'organization_url' => text(
                label: 'What is the organization URL?',
                default: 'http://localhost'
            )
        ];

        $this->info(string: "Please confirm the following options:");
        foreach ($options as $key => $value) {
            $this->info(string: "{$key}: {$value}");
        }

        $confirmed = confirm(
            label: 'Do you want to continue with the above options?',
            default: true
        );

        if (!$confirmed) {
            $this->info(string: "Operation aborted, run the command again to generate the configuration file.");

            exit(1);
        }

        // get the stub path
        $stub = __DIR__ . '/../../stubs/sso.stub';
        $content = file_get_contents($stub);

        $strictMode = $options['strict'] === 'yes';

        $replacements = [
            '{{ $strictMode }}' => $strictMode ? 'true' : 'false',
            '{{ $baseUrl }}' => $options['base_url'],
            '{{ $serviceName }}' => $options['sp_service_name'],
            '{{ $serviceDescription }}' => $options['sp_service_description'],
            '{{ $technicalContactName }}' => $options['contact_technical_name'],
            '{{ $technicalContactEmail }}' => $options['contact_technical_email'],
            '{{ $supportContactName }}' => $options['contact_support_name'],
            '{{ $supportContactEmail }}' => $options['contact_support_email'],
            '{{ $organizationName }}' => $options['organization_name'],
            '{{ $organizationDisplayName }}' => $options['organization_display_name'],
            '{{ $organizationUrl }}' => $options['organization_url']
        ];

        $content = Str::replace(
            search: array_keys($replacements),
            replace: array_values($replacements),
            subject: $content
        );

        $configPath = config_path(path: 'sso.php');
        file_put_contents(filename: $configPath, data: $content);

        $this->info(string: "SSO configuration file generated successfully.");
        $this->info(string: "Updating the .env with the required values.");

        $this->updateEnvFile(data: [
            "SAML_SP_ENTITY_ID" => $options['sp_entity_id'],
            "SAML_SP_ACS_URL" => $options['sp_acs_url'],
            "SAML_SP_SLS_URL" => $options['sp_sls'],
            "SAML_IDP_ENTITY_ID" => $options['idp_entity_id'],
            "SAML_IDP_SSO_URL" => $options['idp_sso_url'],
            "SAML_IDP_SLS_URL" => $options['idp_sls_url'],
            "SAML_CERTIFICATE" => $options['idp_x509_cert'],
        ]);

        // Ask if the user wants to publish the controller
        $publishController = confirm(
            label: 'Do you want to publish the controller?',
            default: false,
            hint: 'Recommended if you need to change the controller functions'
        );

        if ($publishController) {
            $this->call(command: 'vendor:publish', arguments: [
                '--provider' => 'ModusDigital\LaravelMicrosoftSso\MicrosoftSsoServiceProvider',
                '--tag' => 'sso-controllers'
            ]);
        }

        $this->info(string: "\nEnvironment file updated successfully.");
        $this->info(string: "SSO configuration completed successfully, don't forget to configure the urls in the azure portal");
        exit(0);
    }

    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            if (str_contains($content, "$key=")) {
                // Replace the existing value
                $content = preg_replace("/^$key=.*/m", "$key=$value", $content);
            } else {
                // Add the new variable
                $content .= "\n$key=$value";
            }
        }

        file_put_contents($envPath, $content);
    }

    protected function setEnvValue($envPath, $key, $value): void
    {
        $str = file_get_contents($envPath);

        $keyPosition = strpos($str, "{$key}=");
        if ($keyPosition === false) {
            // Key doesn't exist, add it to the end
            $str .= "\n{$key}={$value}\n";
        } else {
            // Replace the existing value
            $endOfLinePosition = strpos($str, "\n", $keyPosition);
            $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
            $str = str_replace($oldLine, "{$key}={$value}", $str);
        }

        file_put_contents($envPath, $str);
    }
}
