<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/admin/PageHeader.vue';
import { help } from '@/routes/admin';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Help', href: help() }] },
});

const jobs = [
    {
        q: 'A new litter is planned or expected',
        a: [
            'Go to Litters and tap New litter.',
            'Choose the mother (dam) and father (sire) from your dogs. Fill in the dates you know; you can leave the rest empty and come back later.',
            'Set the status to Planned (thinking about it) or Expected (bred, waiting for puppies).',
            'Leave "Show on the website" ticked and visitors will see the litter on the Litters page.',
        ],
    },
    {
        q: 'The puppies were born',
        a: [
            'Open the litter and change the status to On the ground. Add the birth date.',
            'Under Puppies, tap + Add girl puppy or + Add boy puppy once for each pup. A collar colour (the coloured ribbon or collar you put on each one) is the easiest way to tell them apart.',
            'Use Weigh-ins to record weights, and Puppy diary to post updates visitors can read.',
        ],
    },
    {
        q: 'Someone calls or texts about a puppy',
        a: [
            'Go to Waitlist and tap Add to waitlist. Pick the person if they are already in Contacts, or type a new name and number.',
            'Choose the litter they want and what they prefer (boy or girl, colour).',
            'Add notes about the conversation. You can keep adding notes later on their Contacts page.',
        ],
    },
    {
        q: 'Someone fills in the application on the website',
        a: [
            'You get an email, and they appear on your Dashboard and the Waitlist as a New application.',
            'Open it to read their answers. When you have called or written back, change the status to Contacted.',
        ],
    },
    {
        q: 'Someone pays a deposit',
        a: [
            'Open their Waitlist entry. Set the status to Deposit paid, enter the amount and the date you received it.',
            'The pick order is the order people choose their puppy in. Pick 1 chooses first. Each number can only be used once per litter.',
        ],
    },
    {
        q: 'A puppy is chosen or goes home',
        a: [
            'Open the litter. On that puppy, set the status to Reserved when someone has picked it, and Gone home on go-home day. Choose the buyer from the list and enter the price.',
            'When every puppy is Gone home or Kept, the litter is marked All gone home for you.',
        ],
    },
    {
        q: 'Add photos',
        a: [
            'Photos in the Photos section are private until you tick Show on the website. Use this section to keep every photo, even ones you never want online.',
            'To show photos on a dog or litter page, open that dog or litter and add them in its Photos box.',
            'Make albums (like "Vet visits" or "Summer litter") to stay organised. Albums are only for you; visitors never see them.',
        ],
    },
    {
        q: 'Hide something from the website',
        a: [
            'Dogs and litters have a Show on the website box. Untick it and they disappear from the public site, but nothing is deleted.',
            'Photos: use the Website / Private badge on the photo, or select several photos and tap Make private.',
            'Anything marked private, like notes, prices you paid, and contact details, is never shown on the website.',
        ],
    },
    {
        q: 'Change my phone number or the website wording',
        a: [
            'Go to Website text. Phone, email, town and Facebook are at the top. The boxes below become the How it works page; leave a box empty and it will not appear.',
            'Changes show on the website straight away.',
        ],
    },
    {
        q: 'Keep a copy of my information',
        a: [
            'On Website text there is a Download your records section. Each button saves a spreadsheet you can open in Excel or Google Sheets.',
            'Photos are not in those files. Ask whoever set up the website how photos are backed up.',
        ],
    },
];

const glossary = [
    ['Dam', 'The mother dog.'],
    ['Sire', 'The father dog.'],
    ['Stud', 'A male dog used for breeding.'],
    ['Litter', 'One set of puppies born to the same mother at the same time.'],
    [
        'Whelping',
        'Giving birth to puppies. The whelping box is the pen where she has them.',
    ],
    [
        'Go-home date',
        'The day puppies leave for their new families. Most breeders wait until 8 weeks old.',
    ],
    [
        'Collar colour',
        'A coloured ribbon or collar so you can tell each puppy apart before they have names.',
    ],
    ['Deposit', 'Money a buyer pays up front to hold their place on the list.'],
    [
        'Waitlist',
        'People waiting for a puppy, in the order they get to choose.',
    ],
    [
        'Pick order',
        'Who chooses first, second, third... when the puppies are old enough to pick. Pick 1 chooses first.',
    ],
    [
        'Registered name',
        'The long official name on the dog’s papers (for example "Snow Goose’s Bella of the Marsh"), not the everyday name.',
    ],
    [
        'OFA',
        'The Orthopedic Foundation for Animals. Vets send hip, elbow and other X-rays there to be graded. A pass is a good sign for the puppies.',
    ],
    [
        'EIC',
        'Exercise-induced collapse, a gene test. "Clear" means the dog does not carry it.',
    ],
    [
        'CNM',
        'Centronuclear myopathy, a muscle condition in Labs, found by a DNA test. "Clear" is what you want to see.',
    ],
    [
        'PRA',
        'Progressive retinal atrophy, an eye condition found by a DNA test.',
    ],
    ['Eye exam (CAER)', 'A yearly check by a dog eye specialist.'],
    [
        'CHIC',
        'A certificate that a dog has had the main health tests for its breed. The checklist on a dog’s page shows what is done.',
    ],
    [
        'Microchip',
        'A rice-grain-sized chip under the skin with a 15-digit number, put in by the vet.',
    ],
    [
        'Heat cycle',
        'When a female can be bred, about every 6 to 8 months, lasting about 3 weeks. The Dogs page helps you track it.',
    ],
];
</script>

<template>
    <Head title="Help" />

    <div class="max-w-3xl space-y-10 p-4 md:p-6">
        <PageHeader
            title="Help"
            description="Plain answers to the things you will do most. Nothing here changes your information."
        />

        <section class="space-y-3 rounded-xl border bg-card p-5">
            <h2 class="font-serif text-xl font-semibold">Start here</h2>
            <ol class="list-decimal space-y-2 pl-5">
                <li>
                    <strong>Website text</strong>: add your phone, email and
                    town so people can reach you.
                </li>
                <li>
                    <strong>Dogs</strong>: add your mothers and fathers, with a
                    photo if you can.
                </li>
                <li>
                    <strong>Litters</strong>: add the litter you are expecting
                    or have on the ground.
                </li>
                <li>
                    <strong>Waitlist</strong>: add the people waiting for a
                    puppy.
                </li>
                <li>
                    <strong>Photos</strong>: keep all your photos here and
                    choose which ones the website shows.
                </li>
            </ol>
            <p class="text-sm text-muted-foreground">
                You do not have to fill in everything. Only fields with a red
                <span class="text-red-600">*</span> are needed. You can always
                come back and add more later.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">
                What do visitors see?
            </h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border bg-card p-4">
                    <p class="font-semibold">Shown on the website</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm">
                        <li>
                            Dogs and litters with
                            <em>Show on the website</em> ticked
                        </li>
                        <li>
                            Their names, colours, descriptions and health tests
                            marked public
                        </li>
                        <li>Prices and deposit on public litters</li>
                        <li>Photos marked <em>Show on the website</em></li>
                        <li>Puppy diary entries marked public</li>
                        <li>Your phone, email and town from Website text</li>
                    </ul>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <p class="font-semibold">Always private</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm">
                        <li>Contacts and their conversation notes</li>
                        <li>The waitlist and who paid a deposit</li>
                        <li>Private notes on dogs and litters</li>
                        <li>Who bought which puppy, and the price</li>
                        <li>Albums, and photos marked Private</li>
                        <li>Microchip and registration numbers</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">How do I…?</h2>
            <div class="divide-y rounded-xl border bg-card">
                <details v-for="j in jobs" :key="j.q" class="group p-4">
                    <summary class="cursor-pointer font-medium select-none">
                        {{ j.q }}
                    </summary>
                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm">
                        <li v-for="step in j.a" :key="step">{{ step }}</li>
                    </ol>
                </details>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">Words you will see</h2>
            <dl class="divide-y rounded-xl border bg-card">
                <div
                    v-for="[term, meaning] in glossary"
                    :key="term"
                    class="grid gap-1 p-3 sm:grid-cols-[11rem_1fr] sm:gap-4"
                >
                    <dt class="font-medium">{{ term }}</dt>
                    <dd class="text-sm text-muted-foreground">{{ meaning }}</dd>
                </div>
            </dl>
        </section>

        <section class="space-y-3 rounded-xl border bg-card p-5">
            <h2 class="font-serif text-xl font-semibold">
                If something looks wrong
            </h2>
            <ul class="list-disc space-y-1.5 pl-5 text-sm">
                <li>
                    <strong>A red message under a box</strong> tells you what to
                    fix. You do not lose what you typed.
                </li>
                <li>
                    <strong
                        >I changed something but the website looks the
                        same.</strong
                    >
                    Refresh the page. Check the item has
                    <em>Show on the website</em> ticked.
                </li>
                <li>
                    <strong>I deleted something by mistake.</strong> Deleting
                    cannot be undone, but photos are always kept in your Photos
                    library. Ask for help right away.
                </li>
                <li>
                    <strong>I forgot my password.</strong> Use
                    <em>Forgot password</em> on the login page, or ask whoever
                    set up the site.
                </li>
                <li>
                    <strong>A photo will not upload.</strong> Try again with
                    fewer photos at a time. If it keeps failing, tell whoever
                    set up the site; the server may have a size limit.
                </li>
            </ul>
        </section>
    </div>
</template>
