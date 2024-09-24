<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens,Notifiable;
    protected $fillable = [
        'user_id',
        'sponsor_id',
        'name',
        'phone',
        'phone_pay_no',
        'ustd_no',
        'email',
        'registration_code',
        'password',
        'is_active',
        'is_green',
        'token', // Include 'token' here if you want to fill it directly
        'package_id',	
        'seven_active',	
        'received_payments_count',	
        'activated_date',	
        'status',	
        'block_reason',	
        'green_date'
    ];

    

    // User.php
    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id','sponsor_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'user_id','sponsor_id');
    }

    public function receiveMoney()
    {
        $this->increment('received_payments_count');
        
        // Check if the user should be upgraded
        $this->checkForPackageUpgrade();
    }

    public function checkForPackageUpgrade()
    {
        $currentPackage = $this->package;
        
        if ($currentPackage) {
            // Find the next package
            $nextPackage = Package::where('member', '<=', $this->received_payments_count)
                                  ->where('id', '>', $currentPackage->id)
                                  ->orderBy('id')
                                  ->first();
            
            if ($nextPackage) {
                $this->update(['package_id' => $nextPackage->id, 'received_payments_count' => 0]);
            }
        }
    }
    
    // public function upgradeToGold()
    // {
    //     $this->update(['package_id' => '3', 'received_payments_count' => 0]);
    // }
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
    // public function sponsor()
    // {
    //     return $this->belongsTo(User::class, 'sponsor_id');
    // }
    public function sponsor() {
        return $this->belongsTo(User::class, 'sponsor_id', 'user_id');
    }
    
    public function getTotalDescendantCount()
    {
        $count = $this->children()->count();

        foreach ($this->children as $child) {
            $count += $child->getTotalDescendantCount();
        }

        return $count;
    }
    public function bankDetails(){
        return $this->belongsTo(Bank::class, 'user_id','user_id');

    }
    public function directReferrals()
    {
        return $this->hasMany(User::class, 'sponsor_id', 'user_id')->select('user_id','name','phone','created_at','activated_date','sponsor_id','status');
    }

    // Recursive relationship to get all referrals (direct and indirect)
    public function allReferralsFlat()
    {
        $referrals = $this->directReferrals;
        foreach ($this->directReferrals as $referral) {
            $referrals = $referrals->merge($referral->allReferralsFlat());
        }
        return $referrals;
    }
   
    // Recursive relationship to get all downlines
    public function allReferrals()
    {
        return $this->directReferrals()->with('allReferrals');
    }
    
}
