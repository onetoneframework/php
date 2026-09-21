import * as resonanceAudioModule from "https://esm.sh/resonance-audio@1.0.0";

const resolvedResonanceAudio =
	resonanceAudioModule.ResonanceAudio
	?? resonanceAudioModule.default?.ResonanceAudio
	?? resonanceAudioModule.default;

if (!resolvedResonanceAudio) {
	throw new Error("Failed to resolve the ResonanceAudio constructor.");
}

export const ResonanceAudio = resolvedResonanceAudio;
export default resolvedResonanceAudio;
