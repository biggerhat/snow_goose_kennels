export type Option = { value: string; label: string };

export type Options = {
    sex: Option[];
    role: Option[];
    color: Option[];
    litterStatus: Option[];
    puppyStatus: Option[];
    contactType: Option[];
    waitlistStatus: Option[];
    leftReason: Option[];
    breedingMethod: Option[];
    registrationType: Option[];
    documentCategory: Option[];
    healthRecordType: Option[];
    achievementKind: Option[];
};

export type Achievement = {
    id: number;
    title: string;
    full_name: string | null;
    kind: string;
    earned_on: string | null;
    event: string | null;
    notes: string | null;
};

export type HealthRecord = {
    id: number;
    type: string;
    name: string;
    given_on: string;
    next_due_on: string | null;
    given_by: string | null;
    lot_number: string | null;
    notes: string | null;
};

export type DocumentFile = {
    id: number;
    category: string;
    title: string;
    original_name: string;
    mime: string;
    size: number;
    issued_on: string | null;
    expires_on: string | null;
    notes: string | null;
    url: string;
};

export type Photo = {
    id: number;
    url: string;
    thumb_url: string;
    caption: string | null;
    credit: string | null;
    is_public: boolean;
    in_gallery: boolean;
    photoable_type: string | null;
    subject?: string | null;
    album_id?: number | null;
    taken_on?: string | null;
};

export type Album = {
    id: number;
    name: string;
    description: string | null;
    photos_count: number;
};

export type HealthTest = {
    id: number;
    name: string;
    result: string;
    tested_on: string | null;
    certificate_number: string | null;
    verification_url: string | null;
    is_public: boolean;
    notes: string | null;
};

export type Dog = {
    id: number;
    slug: string;
    name: string;
    registered_name: string | null;
    sex: string;
    role: string;
    color: string | null;
    birth_date: string | null;
    bio: string | null;
    is_public: boolean;
    registry_number: string | null;
    microchip: string | null;
    private_notes: string | null;
    is_ours?: boolean;
    owner_contact_id?: number | null;
    owner_contact?: { id: number; name: string } | null;
    sire_id?: number | null;
    dam_id?: number | null;
    titles?: string | null;
    dna_profile?: string | null;
    co_owner_name?: string | null;
    acquired_on?: string | null;
    left_on?: string | null;
    left_reason?: string | null;
    deleted_at?: string | null;
    purge_blocked_until?: string | null;
    age_label?: string | null;
    photos?: Photo[];
    documents?: DocumentFile[];
    health_records?: HealthRecord[];
    achievements?: Achievement[];
    health_tests?: HealthTest[];
    heat_cycles?: {
        id: number;
        started_on: string;
        ended_on: string | null;
        bred_on: string | null;
        notes: string | null;
    }[];
};

export type Puppy = {
    id: number;
    name: string | null;
    collar: string | null;
    sex: string;
    color: string | null;
    birth_weight_g: number | null;
    status: string;
    contact_id: number | null;
    sale_price: number | null;
    notes: string | null;
    microchip?: string | null;
    registration_type?: string | null;
    akc_number?: string | null;
    papers_given_on?: string | null;
    went_home_on?: string | null;
    vet_checked_on?: string | null;
    reunite_enrolled_on?: string | null;
    buyer?: { id: number; name: string } | null;
    documents?: DocumentFile[];
    health_records?: HealthRecord[];
    weights?: { id: number; weighed_on: string; weight_g: number }[];
};

export type Contact = {
    id: number;
    name: string;
    type: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    notes: string | null;
    waitlist_entries_count?: number;
    contact_notes?: { id: number; body: string; noted_at: string }[];
    waitlist_entries?: WaitlistEntry[];
    puppies?: (Puppy & {
        litter?: { id: number; slug: string; title: string };
    })[];
    documents?: DocumentFile[];
};

export type LitterLite = {
    id: number;
    slug: string;
    title: string;
    status: string;
};

export type Litter = {
    id: number;
    slug: string;
    title: string;
    name: string | null;
    sire_id: number | null;
    dam_id: number | null;
    sire?: { id: number; name: string } | null;
    dam?: { id: number; name: string } | null;
    status: string;
    bred_on: string | null;
    due_on: string | null;
    born_on: string | null;
    go_home_on: string | null;
    price: number | null;
    deposit: number | null;
    expected_count: number | null;
    description: string | null;
    is_public: boolean;
    private_notes: string | null;
    deleted_at?: string | null;
    purge_blocked_until?: string | null;
    breeding_method?: string | null;
    bred_place?: string | null;
    bred_handler?: string | null;
    stud_fee?: number | null;
    stud_fee_paid_on?: string | null;
    stillborn_count?: number | null;
    akc_applied_on?: string | null;
    akc_litter_number?: string | null;
    sire_owner_signed_on?: string | null;
    dam_owner_signed_on?: string | null;
    multiple_sires?: boolean;
    akc_marketplace_listed_on?: string | null;
    puppies_count?: number;
    next_step?: string | null;
    waitlist_entries_count?: number;
    puppies?: Puppy[];
    photos?: Photo[];
    documents?: DocumentFile[];
    diary?: {
        id: number;
        posted_on: string;
        title: string | null;
        body: string;
        is_public: boolean;
    }[];
    waitlist_entries?: WaitlistEntry[];
};

export type WaitlistEntry = {
    id: number;
    contact_id: number;
    litter_id: number | null;
    puppy_id: number | null;
    preferred_sex: string | null;
    preferred_color: string | null;
    pick_order: number | null;
    status: string;
    deposit_amount: number | null;
    deposit_paid_on: string | null;
    notes: string | null;
    created_at: string;
    source?: string;
    application?: Record<string, string> | null;
    contact?: {
        id: number;
        name: string;
        phone?: string | null;
        email?: string | null;
    };
    litter?: LitterLite | null;
    puppy?: { id: number; name: string | null; collar: string | null } | null;
};

export type Testimonial = {
    id: number;
    name: string;
    location: string | null;
    puppy_name: string | null;
    body: string;
    is_public?: boolean;
};
