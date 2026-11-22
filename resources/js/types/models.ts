export type Option = { value: string; label: string };

export type Options = {
    sex: Option[];
    role: Option[];
    color: Option[];
    litterStatus: Option[];
    puppyStatus: Option[];
    contactType: Option[];
    waitlistStatus: Option[];
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
    age_label?: string | null;
    photos?: Photo[];
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
    buyer?: { id: number; name: string } | null;
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
    puppies_count?: number;
    waitlist_entries_count?: number;
    puppies?: Puppy[];
    photos?: Photo[];
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
