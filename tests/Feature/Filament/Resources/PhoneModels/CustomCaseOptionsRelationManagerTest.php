<?php

namespace Tests\Feature\Filament\Resources\PhoneModels;

use App\Filament\Resources\PhoneModels\Pages\EditPhoneModel;
use App\Filament\Resources\PhoneModels\RelationManagers\CustomCaseOptionsRelationManager;
use App\Models\CaseType;
use App\Models\CustomCaseOption;
use App\Models\PhoneModel;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomCaseOptionsRelationManagerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function priceData(CaseType $caseType): array
    {
        return ['case_type_id' => $caseType->id, 'price_jpy' => 3200, 'price_idr' => 325000, 'cost_price_idr' => 90000, 'is_available' => true];
    }

    public function test_admin_adds_price_for_a_case_type(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $phoneModel = PhoneModel::factory()->withPreview()->create();
        $caseType = CaseType::factory()->create();

        Livewire::test(CustomCaseOptionsRelationManager::class, ['ownerRecord' => $phoneModel, 'pageClass' => EditPhoneModel::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), $this->priceData($caseType))
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('custom_case_options', ['phone_model_id' => $phoneModel->id, 'case_type_id' => $caseType->id, 'price_jpy' => 3200, 'cost_price_idr' => 90000]);
    }

    public function test_rejects_second_price_for_the_same_case_type(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $option = CustomCaseOption::factory()->create();

        Livewire::test(CustomCaseOptionsRelationManager::class, ['ownerRecord' => $option->phoneModel, 'pageClass' => EditPhoneModel::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), $this->priceData($option->caseType))
            ->assertHasFormErrors(['case_type_id' => 'unique']);
    }

    public function test_same_case_type_is_allowed_on_another_phone_model(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $option = CustomCaseOption::factory()->create();
        $otherModel = PhoneModel::factory()->withPreview()->create();

        Livewire::test(CustomCaseOptionsRelationManager::class, ['ownerRecord' => $otherModel, 'pageClass' => EditPhoneModel::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), $this->priceData($option->caseType))
            ->assertHasNoFormErrors();
    }
}
