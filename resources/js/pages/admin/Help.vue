<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import { topicId as slugOf } from '@/lib/helpTopic';
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
            'On the litter’s Health & diary tab, use Weigh-ins to record weights, and Puppy diary to post updates visitors can read.',
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
        q: 'Find a dog, puppy, litter or person fast',
        a: [
            'Tap Find… at the top of any page (or press the / key on a computer). Type part of a name, a registration number, a microchip number, a phone number or an email.',
            'Tap a result to open it. It searches dogs (including archived ones), litters, puppies and contacts at once.',
        ],
    },
    {
        q: 'Call someone from your contacts',
        a: [
            'Open Contacts. Anyone with a phone number has a Call button next to their name. On your phone, tap it and the call starts.',
            'Tap the name itself to see their details and everything you have talked about.',
        ],
    },
    {
        q: 'Move around a long page',
        a: [
            'Dog and litter pages are split into tabs along the top, like Details, Health and Papers. Tap a tab to see that part. The tabs slide sideways if there are too many to fit.',
            'The Details tab holds the main form. Its Save changes button is at the very bottom of the form. Boxes on the other tabs, like adding a vaccine or a document, save themselves when you tap their own Add button.',
            'On a litter, each puppy is one line showing its name, status and buyer. Tap the line to open the puppy and change anything.',
            'When you change something, a bar appears at the bottom saying you have changes that are not saved. Tap Save changes there, or Discard to undo.',
            'On a phone, the bar at the bottom has Home, Litters, Dogs and Waitlist. Tap More for everything else.',
        ],
    },
    {
        q: 'Deal with “Needs attention” on the dashboard',
        a: [
            'Each card is something due soon or overdue, like a vaccine, a paper that runs out, or an AKC deadline. Tap a card to go to it.',
            'Not ready to deal with it? Tap Remind me in a week and it disappears for a week, then comes back.',
            'Recording the next dose, or adding the new paper, clears the reminder by itself.',
        ],
    },
    {
        q: 'Fill in the AKC litter application',
        a: [
            'First go to Website text and fill in Kennel owner (your name and mailing address). They are private and only used on AKC forms.',
            'Open the litter, go to the Breeding & AKC tab and tap Fill in the AKC litter application. It lines up everything AKC asks for and lists anything still missing.',
            'Tap Download filled-in AKC form to get AKC’s own PDF with your answers in the boxes. Check every box, print it, sign it in black ink and mail it with a check or money order. Or tap Register online and use the copy buttons.',
            'The sire’s owner has to sign too, and so does every owner of the dam. Write the dates they signed on the litter page.',
            'When you have sent it, enter the date on the litter page. When AKC sends back the litter number, enter that too.',
        ],
    },
    {
        q: 'Plan a pairing before the litter',
        a: [
            'Open Pairing planner in the menu and choose a sire and a dam.',
            'It shows how closely related they are (worked out from the family trees you have entered), what health tests each has, and the AKC points to watch, like age and DNA.',
            'It only knows the ancestors you have entered, so fill in the family trees first. It is a planning aid, not advice.',
            'If you like the pairing, tap Plan this litter to start a litter with both dogs filled in.',
        ],
    },
    {
        q: 'Add a dog’s titles',
        a: [
            'Open the dog, tap the Papers & titles tab and find Titles & awards. Tap Add a title or award, type the letters (like MH), what it stands for, the date and the event.',
            'The letters appear after the dog’s name on your website and in the pedigree, in date order. Once you list titles here, the Titles box fills itself in.',
        ],
    },
    {
        q: 'Archive a dog or a litter',
        a: [
            'Open the dog or litter and tap Archive. It disappears from your lists and the website, but its records, papers and family tree are kept.',
            'AKC asks breeders to keep records for 5 years after a dog dies, is sold or is given away, so a dog or litter cannot be erased for good until 5 years have passed. A dog or litter you added by mistake can be erased right away.',
            'To see archived ones, look under Archived at the bottom of the Dogs or Litters page. Tap Bring back to restore one.',
        ],
    },
    {
        q: 'Get ready for an AKC inspection',
        a: [
            'Open Records. The Check-up tab opens first and shows Inspection readiness. The green bar shows how much is ready. Under it is what would catch your eye first: dog records with gaps, litter records with gaps, overdue vaccines, and no emergency plan.',
            'Under Kennel papers, print the emergency plan worksheet, fill it in, and add a copy of the signed plan as a document. You will be reminded to review it once a year.',
            'The Dogs and Litters tabs have a Print button for each one. Tap Print everything at the top to print every dog and litter record in one go.',
        ],
    },
    {
        q: 'Use a stud or mother from another kennel',
        a: [
            'On the litter, open the Sire (father) or Dam (mother) box. If the dog is not in the list, tap the link under the box to add a dog from another kennel. Fill in the name, registered name, registration number and the owner.',
            'These dogs are kept apart from yours (under Dogs, in Dogs from other kennels) and are never shown on your Our Dogs page. Visitors only see the name on the litter.',
            'On the litter, on the Breeding & AKC tab, record the stud fee and the date it was paid. Keep the signed stud agreement on the Papers & photos tab.',
        ],
    },
    {
        q: 'Show a dog’s family tree',
        a: [
            'Open the dog and choose its father and mother under Family tree. If a parent is not in your list, add them with the link under the box.',
            'The Pedigree card on the dog’s Papers & titles tab draws the tree back several generations. Open each parent and fill in their parents to grow it.',
            'The tree also shows on the dog’s page on your website, with names and titles only. Registration numbers stay private.',
        ],
    },
    {
        q: 'Keep copies of registration papers, OFA results and agreements',
        a: [
            'Open the dog, litter, puppy (inside its card) or contact and find Papers & documents. On a dog or contact it is on the Papers tab. Tap Add a document, choose the file (a PDF or a photo of the paper is fine), say what it is, and add it.',
            'If a paper runs out, like a rabies certificate, fill in Runs out on. You will see a reminder on the dashboard and in the morning email.',
            'Documents are private. Only you can open them, and they are never shown on the website.',
        ],
    },
    {
        q: 'Record vaccines, worming and vet visits',
        a: [
            'On a dog’s page, open the Health tab, find Vaccines, treatments & vet visits and tap Add. Say what it was, the date it was given, and when it is due again.',
            'For puppies, open the litter and its Health & diary tab. Under Litter health, record a deworming day or vet check once and it is added to every puppy. A single puppy’s records are in its own card.',
            'When something is due soon or overdue, it shows under Needs attention on the dashboard and in your morning email. Once you record the next dose, the reminder clears.',
        ],
    },
    {
        q: 'Get a litter ready for AKC',
        a: [
            'Open the litter, tap the Breeding & AKC tab and fill it in. The AKC checklist under it shows what is done and what is missing: both parents’ registration numbers, how old each parent was, DNA for the sire when AKC needs it, and the application deadline.',
            'AKC asks you to send the litter application within 6 months of the birth date. When you send it, enter the date; when AKC sends the papers back, enter the AKC litter number.',
            'For each puppy, tap its line on the Puppies tab to open it, then open Go-home & AKC details to record the microchip, whether it gets full or limited registration, and the date you hand over the papers. Add the buyer’s name and address on the Contacts page.',
            'The checklist is a reminder, not a rule book. If you are unsure about something, check akc.org or call AKC.',
        ],
    },
    {
        q: 'Print my records for AKC',
        a: [
            'Open Records in the menu. Tap Print next to a dog or a litter, or Print everything.',
            'A clean page opens. Use Print or Save as PDF. The pages are built from what you have entered, so fill in the dog and litter pages first.',
            'AKC asks you to keep these records for 5 years after a dog dies, is sold or is given away. Mark a dog as Left the kennel instead of deleting it so its records stay.',
        ],
    },
    {
        q: 'Follow the puppy schedule',
        a: [
            'When you enter a litter’s birth date and save, a puppy schedule is made for you. You can also open the litter, tap the Health & diary tab and tap Make the puppy schedule.',
            'It lists the usual steps counted from the birth date: weekly weigh-ins, deworming at 2, 4, 6 and 8 weeks, starting solid food, the first vaccines, the vet check and microchips, sending the AKC application, and go-home day.',
            'Tick a step when it is done. A step that is due soon or late shows on your dashboard under Needs attention, and in the morning email.',
            'It is a typical schedule, not vet advice. Tap the small date box to move a step, tap the bin to remove one, or add your own step at the bottom. Ask your vet about vaccines and worming.',
        ],
    },
    {
        q: 'Email everyone waiting for a litter',
        a: [
            'Open the litter, tap the Waitlist tab and find Send an update by email.',
            'Tick who to send to (everyone with an email address is ticked), write a subject and your message, and tap Send. Each person gets their own email, so nobody sees anyone else’s address. Replies come to your own email.',
            'People who are already Placed or have Passed are left out, and anyone without an email address cannot be reached by email: call or text them.',
            'Every email you send is kept under Sent so far, and a note is added to each person’s Conversations on their contact page.',
        ],
    },
    {
        q: 'Print the go-home packet for a buyer',
        a: [
            'First go to Website text and open the Buyer papers tab. Write what the puppies eat, your first-weeks tips and your vet’s name. Leave a box empty and it is left out of the packet.',
            'Open the litter, go to the Puppies tab and tap the puppy’s line to open it. Tap Print go-home packet.',
            'The packet shows the puppy’s details, registration and microchip, every vaccine and worming you recorded and what is due next, plus your food and care notes. It is only as complete as what you have entered, so fill in the puppy first.',
        ],
    },
    {
        q: 'Print a sales agreement and a deposit receipt',
        a: [
            'On the Puppies tab of the litter, open the puppy and tap Print sales agreement. It fills in you, the buyer, the puppy, the price, any deposit received and the balance.',
            'The terms come from what you wrote yourself: the Deposits, Refunds, Health guarantee, Spay / neuter and Take-back boxes under Website text → How it works, plus Extra terms under Buyer papers. If you wrote nothing, it says so and prints no terms. The site never makes up rules for you.',
            'It is not legal advice. Have someone who knows your state’s rules read it before you rely on it. Both of you sign and each keep a copy.',
            'For a deposit receipt, open the person’s Waitlist entry, fill in the deposit amount and date, and tap Print deposit receipt.',
        ],
    },
    {
        q: 'Print a stud agreement',
        a: [
            'Open the litter, go to the Breeding & AKC tab and tap Print a stud agreement.',
            'It lists the sire, the dam and their owners, the date and place of the breeding and the stud fee. Write your usual terms under Website text → Buyer papers → Terms for the stud agreement and they print on it. Otherwise there is room to write them by hand.',
            'AKC still needs the sire’s owner to sign the litter application. This paper does not replace that.',
        ],
    },
    {
        q: 'Keep a whelping log',
        a: [
            'When puppies are born, open each puppy on the litter’s Puppies tab and open Birth details. Write the order it was born (1 for the first), roughly what time, and a note if it needed help.',
            'It is printed on the litter’s record under Records. AKC may ask to see your whelping records.',
        ],
    },
    {
        q: 'Keep track of money',
        a: [
            'Open Money in the menu. Puppy sales and stud fees show up by themselves, from the price on each puppy marked Gone home and the stud fee on each litter.',
            'On the Expenses tab, tap Add expense for each bill: the date, what kind, the amount and who you paid. Choose a litter if it was for one, and it counts toward that litter.',
            'The Summary tab adds it up for a year you choose: what came in, what you spent, and what was left over, by kind of expense and by litter. Tap Expenses spreadsheet to save a copy for your tax preparer.',
            'This is a simple record, not tax advice. Keep your receipts.',
        ],
    },
    {
        q: 'Check yourself against the AKC breeder programs',
        a: [
            'Open Records and tap the AKC programs tab. It shows AKC Bred with H.E.A.R.T. and AKC Breeder of Merit, and ticks off what your records already show: a recent AKC litter, health tests on your breeding dogs, and how many puppies have an AKC number.',
            'Fill in the parts only you know at the bottom: years in AKC events, your breed club, good standing and your last breeder course.',
            'It is only a checklist. AKC decides who qualifies, and its rules change, so tap AKC’s requirements to read them.',
        ],
    },
    {
        q: 'See how many litters a mother has had',
        a: [
            'On a litter, under Pairing & dates, a line under the parents says how many earlier litters the mother has had and how long ago the last was. The Pairing planner shows the same.',
            'It is information, not a rule. Ask your vet how long to rest her between litters.',
        ],
    },
    {
        q: 'Get happy-family stories from my buyers',
        a: [
            'Your website has a Share your story page where families can write about their puppy. Text or email them the link: your website address followed by /share-your-story.',
            'Their story does not show on the website until you approve it. You get an email when one arrives, and Happy families in the menu shows a number.',
            'Open Happy families. Stories waiting for you are at the top. Tap Approve and show, fix a typo with Edit first if you like, or delete it.',
            'You can also type a story in yourself with Add a story, if someone sent it to you by text or Facebook. Ask their permission first.',
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
            'Go to Website text. The Contact details tab has your phone, email, town and Facebook. The How it works tab becomes the How it works part of the Contact page; leave a box empty and it will not appear. Tap Save changes when you are done.',
            'Changes show on the website straight away.',
        ],
    },
    {
        q: 'Keep a copy of my information',
        a: [
            'Open Records and tap the Spreadsheets tab. Each button saves a spreadsheet you can open in Excel or Google Sheets.',
            'Photos and documents are not in those files. Ask whoever set up the website how they are backed up.',
        ],
    },
];

const glossary = [
    [
        'Archive',
        'Hides a dog or litter from your lists and website without erasing it. Its records stay, as AKC asks.',
    ],
    [
        'Co-owner',
        'Someone who owns a dog with you, or leases her. AKC wants them on the litter application and they have to sign it.',
    ],
    [
        'Inbreeding percentage',
        'How much of a puppy’s genes would come from the same ancestors on both sides. Higher means the parents are more closely related. First cousins would be 6.25%.',
    ],
    [
        'Emergency plan',
        'A written plan for fires, floods, power cuts and illness, with phone numbers and where the dogs would go. AKC asks breeders to have one.',
    ],
    [
        'AKC',
        'The American Kennel Club, the main registry for purebred dogs. A registered puppy gets papers from AKC.',
    ],
    [
        'AKC litter application',
        'The form that registers a whole litter with AKC. It is due within 6 months of the puppies being born. AKC sends back a litter number and a registration form for each puppy.',
    ],
    ['AKC litter number', 'The number AKC gives a registered litter.'],
    [
        'DNA profile',
        'A dog’s DNA on file with AKC. AKC requires it for a sire used a lot, for artificial insemination, for imports and for some conditional registrations.',
    ],
    [
        'Full registration',
        'The puppy’s owner can register the puppy and also its own future litters.',
    ],
    [
        'Limited registration',
        'The puppy can be registered, but its offspring cannot.',
    ],
    [
        'Pedigree',
        'A family tree showing a dog’s parents, grandparents and so on.',
    ],
    [
        'Stud fee',
        'What is paid for the use of a stud dog. You may pay it for another kennel’s stud or be paid it for yours.',
    ],
    [
        'Titles',
        'Letters earned in dog sports or shows, like MH (Master Hunter) or SH (Senior Hunter), added after a dog’s name.',
    ],
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
        'Puppy schedule',
        'A checklist of the usual steps for raising a litter, counted from the birth date: weigh-ins, deworming, vaccines, the vet check and go-home day.',
    ],
    [
        'Whelping log',
        'A record of the birth: the order the puppies were born in, the time, and any notes.',
    ],
    [
        'Go-home packet',
        'One printed page per puppy for the new family: details, registration, microchip, vaccines and worming, and your food and care notes.',
    ],
    [
        'Sales agreement',
        'A signed paper between you and the buyer with the puppy, the price, the deposit and your terms.',
    ],
    [
        'Stud agreement',
        'A signed paper between the owners of the sire and the dam about the breeding, the fee and what happens if there are no puppies.',
    ],
    [
        'Bred with H.E.A.R.T.',
        'An AKC program for breeders who health test their breeding dogs, keep up with education and have a recent AKC litter. It is a checklist on the AKC programs tab.',
    ],
    [
        'Breeder of Merit',
        'An AKC program that recognises breeders with years in AKC events, titled dogs, health testing and every puppy registered.',
    ],
    [
        'Heat cycle',
        'When a female can be bred, about every 6 to 8 months, lasting about 3 weeks. The Dogs page helps you track it.',
    ],
];

const query = ref('');
const openId = ref<string | null>(null);

const matches = (text: string) =>
    text.toLowerCase().includes(query.value.trim().toLowerCase());
const filteredJobs = computed(() =>
    jobs.filter(
        (j) => !query.value.trim() || matches(j.q) || j.a.some(matches),
    ),
);
const filteredGlossary = computed(() =>
    glossary.filter(
        ([t, m]) => !query.value.trim() || matches(t) || matches(m),
    ),
);

onMounted(() => {
    const id = window.location.hash.slice(1);

    if (id) {
        openId.value = id;
        void nextTick(() =>
            document.getElementById(id)?.scrollIntoView({ block: 'start' }),
        );
    }
});
</script>

<template>
    <Head title="Help" />

    <div class="max-w-3xl space-y-10 p-4 md:p-6">
        <PageHeader
            title="Help"
            description="Plain answers to the things you will do most. Nothing here changes your information."
        />

        <div
            class="sticky top-0 z-10 -mx-4 space-y-2 border-b bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6"
        >
            <input
                v-model="query"
                type="search"
                aria-label="Search the help"
                placeholder="Search the help: try “deposit”, “AKC”, “photos”…"
                class="h-11 w-full rounded-md border border-input bg-card px-3 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />
            <nav
                v-if="!query"
                aria-label="Help contents"
                class="flex flex-wrap gap-x-4 gap-y-1 text-sm"
            >
                <a href="#start" class="underline underline-offset-2"
                    >Start here</a
                >
                <a href="#visitors" class="underline underline-offset-2"
                    >What visitors see</a
                >
                <a href="#how" class="underline underline-offset-2"
                    >How do I…?</a
                >
                <a href="#words" class="underline underline-offset-2"
                    >Words you will see</a
                >
                <a href="#wrong" class="underline underline-offset-2"
                    >If something looks wrong</a
                >
            </nav>
        </div>

        <section
            v-if="!query"
            id="start"
            class="scroll-mt-28 space-y-3 rounded-xl border bg-card p-5"
        >
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
                <li>
                    <strong>Buyer papers</strong> (optional): under Website
                    text, write how buyers pay, what the puppies eat and your
                    vet, so the go-home packet and agreements are filled in.
                </li>
                <li>
                    <strong>Records</strong>: print your AKC dog and litter
                    records whenever you need them.
                </li>
                <li>
                    <strong>Money</strong>: add what you spend, and see how each
                    litter did.
                </li>
            </ol>
            <p class="text-sm text-muted-foreground">
                You do not have to fill in everything. Only fields with a red
                <span class="text-red-700">*</span> are needed. You can always
                come back and add more later.
            </p>
        </section>

        <section v-if="!query" id="visitors" class="scroll-mt-28 space-y-3">
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
                            Their names, colours, titles, descriptions and
                            health tests marked public
                        </li>
                        <li>A dog’s family tree: names and titles only</li>
                        <li>Prices and deposit on public litters</li>
                        <li>Photos marked <em>Show on the website</em></li>
                        <li>Puppy diary entries marked public</li>
                        <li>
                            Your phone, email and town from Website text, with
                            your How it works wording on the Contact page
                        </li>
                        <li>
                            Happy families stories you approved (including ones
                            families sent in) on the home page
                        </li>
                        <li>A short Privacy page about the forms</li>
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
                        <li>Microchip, registration and DNA numbers</li>
                        <li>Documents, vaccines and vet records</li>
                        <li>
                            Stud fees, AKC details, signatures, and dogs from
                            other kennels
                        </li>
                        <li>Your kennel owner name and address</li>
                        <li>Expenses and your money summary</li>
                        <li>
                            Buyer papers wording, printed agreements and
                            receipts
                        </li>
                        <li>Emails you send to people on the waitlist</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="how" class="scroll-mt-28 space-y-3">
            <h2 class="font-serif text-xl font-semibold">How do I…?</h2>
            <p
                v-if="!filteredJobs.length"
                class="text-sm text-muted-foreground"
            >
                No how-to matches “{{ query }}”.
            </p>
            <div v-else class="divide-y rounded-xl border bg-card">
                <details
                    v-for="j in filteredJobs"
                    :id="slugOf(j.q)"
                    :key="j.q"
                    class="group scroll-mt-28 p-4"
                    :open="!!query || openId === slugOf(j.q)"
                >
                    <summary class="cursor-pointer font-medium select-none">
                        {{ j.q }}
                    </summary>
                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm">
                        <li v-for="step in j.a" :key="step">{{ step }}</li>
                    </ol>
                </details>
            </div>
        </section>

        <section id="words" class="scroll-mt-28 space-y-3">
            <h2 class="font-serif text-xl font-semibold">Words you will see</h2>
            <p
                v-if="!filteredGlossary.length"
                class="text-sm text-muted-foreground"
            >
                No word matches “{{ query }}”.
            </p>
            <dl v-else class="divide-y rounded-xl border bg-card">
                <div
                    v-for="[term, meaning] in filteredGlossary"
                    :key="term"
                    class="grid gap-1 p-3 sm:grid-cols-[11rem_1fr] sm:gap-4"
                >
                    <dt class="font-medium">{{ term }}</dt>
                    <dd class="text-sm text-muted-foreground">{{ meaning }}</dd>
                </div>
            </dl>
        </section>

        <section
            v-if="!query"
            id="wrong"
            class="scroll-mt-28 space-y-3 rounded-xl border bg-card p-5"
        >
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
