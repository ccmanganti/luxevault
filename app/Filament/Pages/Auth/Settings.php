<?php
namespace App\Filament\Pages\Auth;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class Settings extends EditProfile {
    protected static ?string $title = 'Settings';
    public function form(Schema $schema): Schema {
        return $schema->components([
            Section::make('Profile')->description('Your name and image across Luxe Vault.')->schema([
                FileUpload::make('profile_image')->label('Profile photo or logo')->disk('local')->visibility('private')->directory(fn()=>'profile-images/'.auth()->id())->image()->acceptedFileTypes(['image/jpeg','image/png','image/webp'])->maxSize(5120)->imagePreviewHeight('120')->preventFilePathTampering()->helperText('JPG, PNG or WebP, up to 5 MB. Your initials appear when no image is uploaded.'),
                $this->getNameFormComponent(),
            ])->columns(2),
            Section::make('Account')->schema([$this->getEmailFormComponent()]),
            Section::make('Change password')->collapsible()->collapsed()->schema([$this->getPasswordFormComponent(),$this->getPasswordConfirmationFormComponent()])->columns(2),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }
    protected function mutateFormDataBeforeSave(array $data): array {
        $path=$data['profile_image']??null;
        if($path){
            $disk=Storage::disk('local');
            if(!preg_match('/^profile-images\\/'.auth()->id().'\\/[A-Za-z0-9]+\\.(jpg|jpeg|png|webp)$/i',$path)||!$disk->exists($path)||!in_array($disk->mimeType($path),['image/jpeg','image/png','image/webp'],true)||$disk->size($path)>5*1024*1024){
                throw ValidationException::withMessages(['data.profile_image'=>'Upload a valid photo for your profile.']);
            }
        }
        return parent::mutateFormDataBeforeSave($data);
    }
}
