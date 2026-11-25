export type RelationshipType = 'biological' | 'adopted' | 'step';

export interface GedcomEvent {
  date?: string;
  place?: string;
}

export interface GedcomPerson {
  id: string;
  givenName: string;
  familyName: string;
  sex?: 'F' | 'M' | 'X';
  birth?: GedcomEvent;
  death?: GedcomEvent;
  photoUrl?: string;
  photoUrls?: string[];
  note?: string;
  lifeEvents?: Array<{ type: string; date?: string; place?: string; note?: string }>;
}

export interface GedcomUnion {
  id: string;
  partnerIds: string[];
  marriage?: GedcomEvent;
  children: Array<{ personId: string; relationship: RelationshipType }>;
}

export interface GenealogyDocument {
  id: string;
  title: string;
  rootPersonId: string;
  people: Record<string, GedcomPerson>;
  unions: Record<string, GedcomUnion>;
}

export interface DisplayPerson extends GedcomPerson {
  displayName: string;
}

export function displayPerson(person: GedcomPerson): DisplayPerson {
  return {
    ...person,
    displayName: `${person.givenName} ${person.familyName}`.trim(),
  };
}

export function formatDate(event?: GedcomEvent): string {
  if (!event) return '';
  return [event.date, event.place].filter(Boolean).join(' · ');
}

export const demoDocument: GenealogyDocument = {
  id: 'demo-valmont',
  title: 'Maison Valmont',
  rootPersonId: 'p-adrien',
  people: {
    'p-adrien': { id: 'p-adrien', givenName: 'Adrien', familyName: 'Valmont', sex: 'M', birth: { date: '1860', place: 'Lyon' }, death: { date: '1934', place: 'Lyon' }, note: 'Fondateur de la branche Valmont.' },
    'p-claire': { id: 'p-claire', givenName: 'Claire', familyName: 'Valmont', sex: 'F', birth: { date: '1862', place: 'Lyon' }, death: { date: '1940', place: 'Lyon' } },
    'p-elise': { id: 'p-elise', givenName: 'Élise', familyName: 'Valmont', sex: 'F', birth: { date: '1887', place: 'Lyon' }, death: { date: '1961', place: 'Paris' } },
    'p-marius': { id: 'p-marius', givenName: 'Marius', familyName: 'Valmont', sex: 'M', birth: { date: '1889', place: 'Lyon' }, death: { date: '1970', place: 'Paris' } },
    'p-lucien': { id: 'p-lucien', givenName: 'Lucien', familyName: 'Valmont', sex: 'M', birth: { date: '1915', place: 'Paris' }, death: { date: '1988', place: 'Tours' } },
    'p-madeleine': { id: 'p-madeleine', givenName: 'Madeleine', familyName: 'Valmont', sex: 'F', birth: { date: '1917', place: 'Paris' }, death: { date: '1997', place: 'Tours' } },
    'p-alix': { id: 'p-alix', givenName: 'Alix', familyName: 'Valmont', sex: 'F', birth: { date: '1942', place: 'Tours' } },
    'p-noe': { id: 'p-noe', givenName: 'Noé', familyName: 'Valmont', sex: 'M', birth: { date: '1945', place: 'Tours' } },
    'p-juliette': { id: 'p-juliette', givenName: 'Juliette', familyName: 'Lenoir', sex: 'F', birth: { date: '1888', place: 'Lyon' }, death: { date: '1969', place: 'Paris' } },
    'p-antoine': { id: 'p-antoine', givenName: 'Antoine', familyName: 'Valmont', sex: 'M', birth: { date: '1918', place: 'Paris' }, death: { date: '1992', place: 'Paris' } },
    'p-sacha': { id: 'p-sacha', givenName: 'Sacha', familyName: 'Valmont', sex: 'X', birth: { date: '2001', place: 'Paris' }, note: 'Données visibles selon les droits du compte.' },
    'p-iris': { id: 'p-iris', givenName: 'Iris', familyName: 'Valmont', sex: 'F', birth: { date: '2004', place: 'Paris' } },
  },
  unions: {
    'u-adrien-claire': { id: 'u-adrien-claire', partnerIds: ['p-adrien', 'p-claire'], marriage: { date: '1882', place: 'Lyon' }, children: [{ personId: 'p-elise', relationship: 'biological' }, { personId: 'p-marius', relationship: 'biological' }] },
    'u-elise-juliette': { id: 'u-elise-juliette', partnerIds: ['p-elise', 'p-juliette'], marriage: { date: '1910', place: 'Paris' }, children: [{ personId: 'p-lucien', relationship: 'biological' }, { personId: 'p-madeleine', relationship: 'biological' }] },
    'u-marius-second': { id: 'u-marius-second', partnerIds: ['p-marius', 'p-antoine'], marriage: { date: '1914', place: 'Paris' }, children: [] },
    'u-lucien-madeleine': { id: 'u-lucien-madeleine', partnerIds: ['p-lucien', 'p-madeleine'], marriage: { date: '1940', place: 'Tours' }, children: [{ personId: 'p-alix', relationship: 'biological' }, { personId: 'p-noe', relationship: 'adopted' }] },
    'u-alix-sacha': { id: 'u-alix-sacha', partnerIds: ['p-alix', 'p-sacha'], marriage: { date: '2023', place: 'Paris' }, children: [{ personId: 'p-iris', relationship: 'step' }] },
  },
};
