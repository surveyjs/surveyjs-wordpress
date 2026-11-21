<?php
// A scripted AI provider for the AI chat E2E tests: it registers with the WordPress AI Client like a real
// provider plugin, so the chat runs its real loop (Connectors, model list, function calling) with no API.

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;

if ( ! class_exists( AiClient::class ) ) {
    return;
}

const SURVEYJS_E2E_AI_OPTION = 'surveyjs_e2e_ai_stub';
const SURVEYJS_E2E_AI_LOG = 'surveyjs_e2e_ai_log';

function surveyjs_e2e_ai_settings() {
    return array_merge( array( 'tools' => true ), (array) get_option( SURVEYJS_E2E_AI_OPTION, array() ) );
}

abstract class SurveyJS_E2E_Stub_Provider implements ProviderInterface {

    abstract protected static function id();

    abstract protected static function label();

    public static function metadata(): ProviderMetadata {
        return new ProviderMetadata( static::id(), static::label(), ProviderTypeEnum::cloud(), 'https://example.com/keys', RequestAuthenticationMethod::apiKey(), 'Scripted provider for SurveyJS E2E tests.' );
    }

    public static function model( string $modelId, ?ModelConfig $modelConfig = null ): ModelInterface {
        $model = new SurveyJS_E2E_Stub_Model( static::modelMetadataDirectory()->getModelMetadata( $modelId ), static::metadata() );
        if ( $modelConfig ) {
            $model->setConfig( $modelConfig );
        }
        return $model;
    }

    public static function availability(): ProviderAvailabilityInterface {
        $id = static::id();
        return new class( $id ) implements ProviderAvailabilityInterface {
            private $id;

            public function __construct( $id ) {
                $this->id = $id;
            }

            public function isConfigured(): bool {
                return null !== AiClient::defaultRegistry()->getProviderRequestAuthentication( $this->id );
            }
        };
    }

    public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface {
        return new SurveyJS_E2E_Stub_Directory( surveyjs_e2e_ai_settings()['tools'] );
    }
}

class SurveyJS_E2E_Stub_Main_Provider extends SurveyJS_E2E_Stub_Provider {
    protected static function id() {
        return 'sjsstub';
    }

    protected static function label() {
        return 'Stub AI';
    }
}

class SurveyJS_E2E_Stub_Google_Provider extends SurveyJS_E2E_Stub_Provider {
    protected static function id() {
        return 'google';
    }

    protected static function label() {
        return 'Google';
    }
}

class SurveyJS_E2E_Stub_Directory implements ModelMetadataDirectoryInterface {
    private $tools;

    public function __construct( $tools ) {
        $this->tools = $tools;
    }

    public function listModelMetadata(): array {
        return array( $this->getModelMetadata( 'stub-large' ), $this->getModelMetadata( 'stub-small' ) );
    }

    public function hasModelMetadata( string $modelId ): bool {
        return in_array( $modelId, array( 'stub-large', 'stub-small' ), true );
    }

    public function getModelMetadata( string $modelId ): ModelMetadata {
        $options = array( new SupportedOption( OptionEnum::systemInstruction() ), new SupportedOption( OptionEnum::maxTokens() ), new SupportedOption( OptionEnum::temperature() ) );
        if ( $this->tools ) {
            $options[] = new SupportedOption( OptionEnum::functionDeclarations() );
        }
        return new ModelMetadata( $modelId, 'stub-large' === $modelId ? 'Stub Large' : 'Stub Small', array( CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory() ), $options );
    }
}

class SurveyJS_E2E_Stub_Model implements ModelInterface, TextGenerationModelInterface {
    private $metadata;
    private $provider;
    private $config;

    public function __construct( ModelMetadata $metadata, ProviderMetadata $provider ) {
        $this->metadata = $metadata;
        $this->provider = $provider;
        $this->config = new ModelConfig();
    }

    public function metadata(): ModelMetadata {
        return $this->metadata;
    }

    public function providerMetadata(): ProviderMetadata {
        return $this->provider;
    }

    public function setConfig( ModelConfig $config ): void {
        $this->config = $config;
    }

    public function getConfig(): ModelConfig {
        return $this->config;
    }

    public function generateTextResult( array $prompt ): GenerativeAiResult {
        $system = (string) $this->config->getSystemInstruction();
        $functions = array_map( function ( $declaration ) {
            return $declaration->getName();
        }, (array) $this->config->getFunctionDeclarations() );
        $request = '';
        $request_index = 0;
        $transcript = array();
        foreach ( $prompt as $index => $message ) {
            foreach ( $message->getParts() as $part ) {
                if ( $part->getType()->isText() ) {
                    $transcript[] = $part->getText();
                    if ( 'user' === $message->getRole()->value ) {
                        $request = (string) $part->getText();
                        $request_index = $index;
                    }
                } elseif ( $part->getType()->isFunctionResponse() ) {
                    $transcript[] = wp_json_encode( $part->getFunctionResponse()->getResponse() );
                }
            }
        }
        $log = (array) get_option( SURVEYJS_E2E_AI_LOG, array() );
        $log[] = array(
            'model'     => $this->metadata->getId(),
            'system'    => $system,
            'functions' => $functions,
            'request'   => $request,
            'prompt'    => implode( "\n", $transcript ),
        );
        update_option( SURVEYJS_E2E_AI_LOG, array_slice( $log, -40 ), false );

        $round = intdiv( count( $prompt ) - 1 - $request_index, 2 );
        $responses = array();
        $last = end( $prompt );
        foreach ( $last->getParts() as $part ) {
            if ( $part->getType()->isFunctionResponse() ) {
                $responses[ $part->getFunctionResponse()->getName() ] = json_decode( (string) wp_json_encode( $part->getFunctionResponse()->getResponse() ), true );
            }
        }
        preg_match( '/form with ID (\d+)/', $system, $match );
        $form_id = (int) ( $match[1] ?? 0 );

        if ( false !== strpos( $request, 'E2E_FAIL_KEY' ) ) {
            throw new RuntimeException( 'invalid x-api-key' );
        }
        if ( false !== strpos( $request, 'E2E_ADD_PHONE' ) ) {
            if ( 0 === $round ) {
                return $this->calls( array( array( 'surveyjs/get-form', array( 'id' => $form_id ) ) ) );
            }
            $form = $responses['wpab__surveyjs__get-form'] ?? null;
            if ( 2 === $round || ! is_array( $form ) ) {
                return $this->text( 'Added a required phone number question. Review it in the editor and click Save.' );
            }
            $json = $form['json'];
            $phone = array(
                'type'       => 'text',
                'name'       => 'phone',
                'title'      => 'Phone number',
                'inputType'  => 'tel',
                'isRequired' => true,
            );
            if ( ! empty( $json['pages'] ) ) {
                $json['pages'][0]['elements'][] = $phone;
            } else {
                $json['elements'][] = $phone;
            }
            return $this->calls( array( array( 'surveyjs/update-form', array( 'id' => $form_id, 'revision' => $form['revision'], 'json' => $json ) ) ) );
        }
        if ( false !== strpos( $request, 'E2E_EMAIL' ) ) {
            if ( 0 === $round ) {
                return $this->calls( array( array( 'surveyjs/update-form-settings', array( 'id' => $form_id, 'settings' => array( 'notificationEnabled' => true, 'notificationTo' => 'chat@example.com' ) ) ) ) );
            }
            return $this->text( 'New entries are now emailed to chat@example.com. Review the Email tab and click Save.' );
        }
        if ( false !== strpos( $request, 'E2E_PROBE' ) ) {
            if ( 0 === $round ) {
                preg_match( '/OTHER=(\d+)/', $request, $other );
                return $this->calls( array(
                    array( 'surveyjs/create-form', array( 'name' => 'Probe', 'json' => array( 'elements' => array( array( 'type' => 'text', 'name' => 'q' ) ) ) ) ),
                    array( 'surveyjs/update-form-settings', array( 'id' => (int) ( $other[1] ?? 0 ), 'settings' => array( 'notificationTo' => 'probe@example.com' ) ) ),
                    array( 'surveyjs/list-entries', array( 'id' => $form_id ) ),
                    array( 'delete_form', array( 'id' => $form_id ) ),
                ) );
            }
            return $this->text( 'PROBE ' . wp_json_encode( $responses ) );
        }
        if ( false !== strpos( $request, 'E2E_GUIDE' ) ) {
            if ( 0 === $round ) {
                return $this->calls( array( array( 'surveyjs/get-plugin-guide', array() ) ) );
            }
            $guide = (string) ( $responses['wpab__surveyjs__get-plugin-guide']['guide'] ?? '' );
            return $this->text( 'Use the **SurveyJS Forms** block or the shortcode.' . ( false !== strpos( $guide, '[surveyjs id=' ) ? ' GUIDE_OK' : '' ) );
        }
        return $this->text( 'Stub reply to: ' . $request );
    }

    private function calls( $calls ) {
        $parts = array();
        foreach ( $calls as $index => $call ) {
            $name = false !== strpos( $call[0], '/' ) ? WP_AI_Client_Ability_Function_Resolver::ability_name_to_function_name( $call[0] ) : $call[0];
            $parts[] = new MessagePart( new FunctionCall( 'call_' . wp_rand() . '_' . $index, $name, $call[1] ) );
        }
        return $this->result( $parts, FinishReasonEnum::toolCalls() );
    }

    private function text( $text ) {
        return $this->result( array( new MessagePart( $text ) ), FinishReasonEnum::stop() );
    }

    private function result( $parts, $reason ) {
        return new GenerativeAiResult( 'stub-' . wp_rand(), array( new Candidate( new ModelMessage( $parts ), $reason ) ), new TokenUsage( 10, 10, 20 ), $this->provider, $this->metadata );
    }
}

add_action(
    'init',
    function () {
        $registry = AiClient::defaultRegistry();
        if ( empty( surveyjs_e2e_ai_settings()['off'] ) && ! $registry->hasProvider( 'sjsstub' ) ) {
            $registry->registerProvider( SurveyJS_E2E_Stub_Main_Provider::class );
        }
        if ( ! empty( surveyjs_e2e_ai_settings()['google'] ) && ! $registry->hasProvider( 'google' ) ) {
            $registry->registerProvider( SurveyJS_E2E_Stub_Google_Provider::class );
        }
    },
    5
);
